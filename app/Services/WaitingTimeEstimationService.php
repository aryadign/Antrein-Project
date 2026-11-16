<?php

namespace App\Services;

use App\Models\Business;
use App\Models\QueueTicket;
use Carbon\Carbon;

class WaitingTimeEstimationService
{
    /**
     * Minimum completed-service samples required before the historical median is
     * considered representative enough to publish an estimate (M18 / P1-10).
     *
     * Justification: the median only carries meaning when a majority of samples
     * agree; below five samples a single outlier can dominate the statistic and
     * produce a misleading number, which Spesifikasi 7.6 explicitly forbids.
     */
    public const int MIN_SERVICE_SAMPLES = 5;

    /**
     * How many days back the estimation samples are collected, in the business's
     * local timezone. Balances recency (staffing and service changes) against
     * sample sufficiency for small UMKMs.
     */
    public const int HISTORY_WINDOW_DAYS = 30;

    /**
     * Upper bound of historical samples read per request to keep the query light.
     */
    public const int MAX_SAMPLES = 200;

    /** Ticket is no longer waiting, so a waiting estimate is not applicable. */
    public const string REASON_NOT_WAITING = 'not_waiting';

    /** Business history is insufficient to compute a trustworthy estimate. */
    public const string REASON_INSUFFICIENT_HISTORY = 'insufficient_history';

    /** Business has no active counter, so no ticket can be called at all. */
    public const string REASON_NO_ACTIVE_COUNTERS = 'no_active_counters';

    public function __construct(protected QueueService $queueService) {}

    /**
     * Estimate how long a waiting customer will wait until their ticket is called,
     * derived from the business's actual historical service durations combined
     * with the current multi-counter serving capacity.
     *
     * Actual waiting time is defined as ticket creation -> first call
     * (created_at -> called_at, written exactly once by QueueService::callNext;
     * recalls only update recalled_at). The prediction basis follows Spesifikasi
     * 7.6 ("durasi layanan digunakan sebagai dasar perkiraan"), upgraded by P1-10
     * to measured historical durations instead of the configured value.
     *
     * The method is strictly read-only: it never mutates tickets, queue order,
     * counters, or quota, and it only ever returns aggregates (never customer
     * historical data) so no PII can leak into responses or logs.
     *
     * @param  int|null  $queuePosition  Precomputed 1-based queue position of a waiting ticket.
     * @return array{available: bool, minutes: int|null, min_minutes: int|null, max_minutes: int|null, range_label: string|null, reason: string|null}
     */
    public function estimateForTicket(QueueTicket $ticket, ?int $queuePosition = null): array
    {
        if ($ticket->status !== QueueTicket::STATUS_MENUNGGU) {
            return [
                'available' => false,
                'minutes' => null,
                'min_minutes' => null,
                'max_minutes' => null,
                'range_label' => null,
                'reason' => self::REASON_NOT_WAITING,
            ];
        }

        $business = $ticket->business;

        $rawActiveCounterCount = $business->activeCounters()->count();
        if ($rawActiveCounterCount < 1) {
            return [
                'available' => false,
                'minutes' => null,
                'min_minutes' => null,
                'max_minutes' => null,
                'range_label' => null,
                'reason' => self::REASON_NO_ACTIVE_COUNTERS,
            ];
        }

        $activeCounterCount = $business->usesStations() ? $rawActiveCounterCount : 1;

        $medianServiceMinutes = $this->historicalMedianServiceMinutes($business);
        if ($medianServiceMinutes === null) {
            return [
                'available' => false,
                'minutes' => null,
                'min_minutes' => null,
                'max_minutes' => null,
                'range_label' => null,
                'reason' => self::REASON_INSUFFICIENT_HISTORY,
            ];
        }

        $queuePosition ??= $this->queueService->calculateQueuePosition($ticket);

        // Free counters can call the leading waiting tickets almost immediately,
        // so the first free counters effectively remove leading queue positions.
        $freeCounterCount = $this->freeCounterCount($business, $activeCounterCount, $ticket);

        $effectiveAheadCount = max(0, $queuePosition - $freeCounterCount);

        // Every waiting-ahead ticket consumes one serving slot: with C counters
        // serving in parallel and a median service duration of S minutes, calls
        // occur roughly every S/C minutes while the queue is loaded.
        $minutes = max(0, (int) ceil($effectiveAheadCount * $medianServiceMinutes / $activeCounterCount));

        if ($minutes === 0) {
            $minMinutes = 0;
            $maxMinutes = 5;
        } else {
            $minMinutes = max(1, (int) floor($minutes * 0.8));
            $maxMinutes = max($minMinutes + 5, (int) ceil($minutes * 1.25));
        }

        return [
            'available' => true,
            'minutes' => $minutes,
            'min_minutes' => $minMinutes,
            'max_minutes' => $maxMinutes,
            'range_label' => sprintf('%d–%d menit', $minMinutes, $maxMinutes),
            'reason' => null,
        ];
    }

    /**
     * Compute the median actual service duration (minutes) from the business's
     * recent completed tickets, or null when history is insufficient or unusable.
     *
     * Samples are tenant-scoped to the business and drawn from a window bounded
     * in the business's local timezone (converted to the application timezone for
     * exact query scoping, mirroring MerchantReportService). Anonymized tickets
     * (ADR-13) remain valid samples because only timestamps are read.
     */
    protected function historicalMedianServiceMinutes(Business $business): ?float
    {
        $timezone = $business->timezone ?: 'Asia/Makassar';
        $appTimezone = (string) config('app.timezone', 'Asia/Makassar');

        $cutoff = Carbon::now($timezone)
            ->subDays(self::HISTORY_WINDOW_DAYS)
            ->startOfDay()
            ->setTimezone($appTimezone);

        $samples = QueueTicket::query()
            ->where('business_id', $business->id)
            ->where('status', QueueTicket::STATUS_SELESAI)
            ->where('created_at', '>=', $cutoff)
            ->whereNotNull('service_started_at')
            ->whereNotNull('completed_at')
            ->orderByDesc('created_at')
            ->limit(self::MAX_SAMPLES)
            ->get(['service_started_at', 'completed_at']);

        $durationMinutes = [];
        foreach ($samples as $sample) {
            // Defensive validity: a service duration must be a non-negative span.
            // Note: Carbon 3 diffInSeconds() is signed (earlier -> later is positive),
            // mirroring MerchantReportService's duration convention.
            if ($sample->completed_at->gte($sample->service_started_at)) {
                $durationMinutes[] = $sample->service_started_at->diffInSeconds($sample->completed_at) / 60;
            }
        }

        if (count($durationMinutes) < self::MIN_SERVICE_SAMPLES) {
            return null;
        }

        sort($durationMinutes);

        $count = count($durationMinutes);
        $middle = intdiv($count, 2);

        $median = ($count % 2) === 1
            ? $durationMinutes[$middle]
            : ($durationMinutes[$middle - 1] + $durationMinutes[$middle]) / 2;

        // A zero (or negative) median cannot express a meaningful drain rate and
        // must not fabricate an "immediately served" promise.
        return $median > 0 ? $median : null;
    }

    /**
     * Count the currently free active counters for the ticket's operating day.
     *
     * Mirrors QueueService::callNext semantics: a counter is busy while it holds
     * a 'dipanggil' or 'sedang_dilayani' ticket, and a legacy active ticket
     * without a counter assignment blocks calling on every counter.
     */
    protected function freeCounterCount(Business $business, int $activeCounterCount, QueueTicket $ticket): int
    {
        $activeTicketCounters = QueueTicket::query()
            ->where('business_id', $business->id)
            ->where('daily_queue_id', $ticket->daily_queue_id)
            ->whereIn('status', [QueueTicket::STATUS_DIPANGGIL, QueueTicket::STATUS_SEDANG_DILAYANI])
            ->pluck('counter_id');

        if ($activeTicketCounters->contains(null)) {
            return 0;
        }

        $busyCounterCount = $activeTicketCounters->unique()->count();

        return max(0, $activeCounterCount - $busyCounterCount);
    }

    /**
     * Always calculate the dynamic scheduled service time for a waiting ticket
     * based on (peopleAhead * service duration) distributed across active service units,
     * and detect when leftover tickets from earlier booking slots shift the estimate
     * past the ticket's booking_slot_start.
     *
     * @return array{
     *     available: bool,
     *     queue_position: int,
     *     people_ahead: int,
     *     service_duration_minutes: int,
     *     estimated_wait_minutes: int,
     *     min_wait_minutes: int,
     *     max_wait_minutes: int,
     *     wait_range_label: string,
     *     booking_slot_label: string|null,
     *     booking_slot_start_label: string|null,
     *     estimated_serve_time_label: string|null,
     *     estimated_serve_range_label: string|null,
     *     has_earlier_slot_leftovers: bool,
     *     has_delay_shift: bool,
     *     shift_notice: string|null
     * }
     */
    public function estimateScheduleForTicket(QueueTicket $ticket, ?int $queuePosition = null): array
    {
        $ticket->loadMissing(['business', 'service']);
        $business = $ticket->business;
        $timezone = $business?->timezone ?: config('app.timezone', 'Asia/Makassar');
        $tzAbbr = $this->timezoneAbbreviation($timezone);

        $bookingSlotLabel = $ticket->bookingSlotWindowLabel();
        $bookingSlotStartLabel = $ticket->bookingSlotLabel();

        if ($ticket->status !== QueueTicket::STATUS_MENUNGGU) {
            return [
                'available' => false,
                'queue_position' => 0,
                'people_ahead' => 0,
                'service_duration_minutes' => max(1, (int) ($ticket->service?->duration_minutes ?? 15)),
                'estimated_wait_minutes' => 0,
                'min_wait_minutes' => 0,
                'max_wait_minutes' => 0,
                'wait_range_label' => 'Tidak sedang menunggu',
                'booking_slot_label' => $bookingSlotLabel,
                'booking_slot_start_label' => $bookingSlotStartLabel,
                'estimated_serve_time_label' => null,
                'estimated_serve_range_label' => null,
                'has_earlier_slot_leftovers' => false,
                'has_delay_shift' => false,
                'shift_notice' => null,
            ];
        }

        $position = $queuePosition ?? $this->queueService->calculateQueuePosition($ticket);
        $peopleAhead = max(0, $position - 1);
        $serviceMinutes = max(1, (int) ($ticket->service?->duration_minutes ?? 15));
        $activeUnits = $business ? $business->effectiveStationCount() : 1;

        // Dynamic calculation: people ahead * service duration divided across active counters (never null/fallback)
        $waitMinutes = (int) ceil(($peopleAhead * $serviceMinutes) / $activeUnits);

        if ($waitMinutes === 0) {
            $minMinutes = 0;
            $maxMinutes = min(10, $serviceMinutes);
            $waitRangeLabel = 'Segera dipanggil (< '.$maxMinutes.' menit)';
        } else {
            $minMinutes = max(1, (int) floor($waitMinutes * 0.85));
            $maxMinutes = max($minMinutes + 5, (int) ceil($waitMinutes * 1.2));
            $waitRangeLabel = sprintf('%d–%d menit', $minMinutes, $maxMinutes);
        }

        $nowLocal = Carbon::now($timezone);
        $rawServeAt = $nowLocal->copy()->addMinutes($waitMinutes);

        $slotStartLocal = $ticket->booking_slot_start?->copy()->setTimezone($timezone);

        $hasEarlierSlotLeftovers = false;
        if ($slotStartLocal) {
            $hasEarlierSlotLeftovers = QueueTicket::query()
                ->where('daily_queue_id', $ticket->daily_queue_id)
                ->where('status', QueueTicket::STATUS_MENUNGGU)
                ->whereNotNull('booking_slot_start')
                ->where('booking_slot_start', '<', $ticket->booking_slot_start)
                ->exists();
        }

        $estimatedServeAt = ($slotStartLocal && $slotStartLocal->gt($rawServeAt))
            ? $slotStartLocal->copy()
            : $rawServeAt;

        $serveRangeStart = $estimatedServeAt->copy()->format('H:i');
        $serveRangeEnd = $estimatedServeAt->copy()->addMinutes(max(5, (int) round($serviceMinutes * 0.3)))->format('H:i');

        $hasDelayShift = false;
        $shiftNotice = null;
        if ($slotStartLocal && $estimatedServeAt->gt($slotStartLocal) && ($hasEarlierSlotLeftovers || $estimatedServeAt->diffInMinutes($slotStartLocal, true) >= 5)) {
            $hasDelayShift = true;
            $shiftNotice = sprintf(
                'Mohon maaf, terdapat sisa antrean dari slot sebelumnya sehingga estimasi waktu pelayanan Anda bergeser ke sekitar pukul %s %s.',
                $estimatedServeAt->format('H:i'),
                $tzAbbr
            );
        }

        return [
            'available' => true,
            'queue_position' => $position,
            'people_ahead' => $peopleAhead,
            'service_duration_minutes' => $serviceMinutes,
            'estimated_wait_minutes' => $waitMinutes,
            'min_wait_minutes' => $minMinutes,
            'max_wait_minutes' => $maxMinutes,
            'wait_range_label' => $waitRangeLabel,
            'booking_slot_label' => $bookingSlotLabel,
            'booking_slot_start_label' => $bookingSlotStartLabel,
            'estimated_serve_time_label' => $estimatedServeAt->format('H:i').' '.$tzAbbr,
            'estimated_serve_range_label' => $serveRangeStart.' – '.$serveRangeEnd.' '.$tzAbbr,
            'has_earlier_slot_leftovers' => $hasEarlierSlotLeftovers,
            'has_delay_shift' => $hasDelayShift,
            'shift_notice' => $shiftNotice,
        ];
    }

    protected function timezoneAbbreviation(string $timezone): string
    {
        return match ($timezone) {
            'Asia/Jakarta' => 'WIB',
            'Asia/Jayapura' => 'WIT',
            default => 'WITA',
        };
    }
}

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
     * @param  QueueTicket  $ticket
     * @param  int|null  $queuePosition Precomputed 1-based queue position of a waiting ticket.
     * @return array{available: bool, minutes: int|null, reason: string|null}
     */
    public function estimateForTicket(QueueTicket $ticket, ?int $queuePosition = null): array
    {
        if ($ticket->status !== QueueTicket::STATUS_MENUNGGU) {
            return ['available' => false, 'minutes' => null, 'reason' => self::REASON_NOT_WAITING];
        }

        $business = $ticket->business;

        $activeCounterCount = $business->activeCounters()->count();
        if ($activeCounterCount < 1) {
            return ['available' => false, 'minutes' => null, 'reason' => self::REASON_NO_ACTIVE_COUNTERS];
        }

        $medianServiceMinutes = $this->historicalMedianServiceMinutes($business);
        if ($medianServiceMinutes === null) {
            return ['available' => false, 'minutes' => null, 'reason' => self::REASON_INSUFFICIENT_HISTORY];
        }

        $queuePosition ??= $this->queueService->calculateQueuePosition($ticket);

        // Free counters can call the leading waiting tickets almost immediately,
        // so the first free counters effectively remove leading queue positions.
        $freeCounterCount = $this->freeCounterCount($business, $activeCounterCount, $ticket);

        $effectiveAheadCount = max(0, $queuePosition - $freeCounterCount);

        // Every waiting-ahead ticket consumes one serving slot: with C counters
        // serving in parallel and a median service duration of S minutes, calls
        // occur roughly every S/C minutes while the queue is loaded.
        $minutes = (int) ceil($effectiveAheadCount * $medianServiceMinutes / $activeCounterCount);

        return ['available' => true, 'minutes' => max(0, $minutes), 'reason' => null];
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
}

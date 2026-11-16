<?php

namespace App\Services;

use App\Models\Business;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;

class BookingSlotService
{
    public const int SLOT_DURATION_MINUTES = 60;

    /**
     * Calculate the maximum dynamic ticket capacity per hourly booking slot
     * based on the service duration and active counters/service units.
     */
    public function calculateSlotCapacity(Business $business, Service $service): int
    {
        $activeUnitsCount = $business->effectiveStationCount();
        $serviceMinutes = max(1, (int) ($service->duration_minutes ?: 15));

        $perUnitPerSlot = max(1, (int) floor(self::SLOT_DURATION_MINUTES / $serviceMinutes));

        return max(1, $perUnitPerSlot * $activeUnitsCount);
    }

    /**
     * Generate today's booking slots for a given business and service with real-time availability.
     *
     * @return Collection<int, array{
     *     slot_key: string,
     *     label: string,
     *     start_local: Carbon,
     *     end_local: Carbon,
     *     start_app: Carbon,
     *     end_app: Carbon,
     *     booked_count: int,
     *     capacity: int,
     *     remaining: int,
     *     is_started: bool,
     *     is_full: bool,
     *     is_available: bool
     * }>
     */
    public function getSlotsForService(Business $business, Service $service, ?DailyQueue $dailyQueue = null): Collection
    {
        $timezone = $business->timezone ?: config('app.timezone', 'Asia/Makassar');
        $appTimezone = (string) config('app.timezone', 'Asia/Makassar');
        $nowLocal = Carbon::now($timezone);

        $operatingDate = $dailyQueue?->queue_date
            ? Carbon::parse($dailyQueue->queue_date->toDateString(), $timezone)->startOfDay()
            : $nowLocal->copy()->startOfDay();

        $capacity = $this->calculateSlotCapacity($business, $service);

        $windows = $this->buildSlotWindows($business, $operatingDate, $nowLocal, $timezone);
        if ($windows->isEmpty()) {
            return collect();
        }

        $bookedCounts = collect();
        if ($dailyQueue) {
            $bookedCounts = QueueTicket::query()
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', '!=', QueueTicket::STATUS_DIBATALKAN)
                ->whereNotNull('booking_slot_start')
                ->get(['booking_slot_start'])
                ->groupBy(fn (QueueTicket $ticket): string => $ticket->booking_slot_start->copy()->setTimezone($timezone)->format('H:i'))
                ->map->count();
        }

        return $windows->map(function (array $window) use ($bookedCounts, $capacity, $nowLocal, $appTimezone): array {
            /** @var Carbon $startLocal */
            $startLocal = $window['start'];
            /** @var Carbon $endLocal */
            $endLocal = $window['end'];
            $slotKey = $startLocal->format('H:i');

            $bookedCount = (int) ($bookedCounts->get($slotKey) ?? 0);
            $isStarted = $startLocal->lte($nowLocal);
            $isFull = $bookedCount >= $capacity;

            return [
                'slot_key' => $slotKey,
                'label' => $slotKey.' – '.$endLocal->format('H:i'),
                'start_local' => $startLocal,
                'end_local' => $endLocal,
                'start_app' => $startLocal->copy()->setTimezone($appTimezone),
                'end_app' => $endLocal->copy()->setTimezone($appTimezone),
                'booked_count' => $bookedCount,
                'capacity' => $capacity,
                'remaining' => max(0, $capacity - $bookedCount),
                'is_started' => $isStarted,
                'is_full' => $isFull,
                'is_available' => ! $isStarted && ! $isFull,
            ];
        });
    }

    /**
     * Resolve and validate the booking slot for an online customer registration.
     *
     * @return array{start: Carbon, end: Carbon, slot_key: string, label: string}
     *
     * @throws DomainException
     */
    public function resolveSlotForOnline(
        Business $business,
        Service $service,
        DailyQueue $dailyQueue,
        ?string $requestedSlot = null
    ): array {
        $timezone = $business->timezone ?: config('app.timezone', 'Asia/Makassar');
        $nowLocal = Carbon::now($timezone);
        $slots = $this->getSlotsForService($business, $service, $dailyQueue);

        if ($requestedSlot !== null && trim($requestedSlot) !== '') {
            $normalizedKey = $this->normalizeRequestedSlotKey(trim($requestedSlot), $timezone);

            if ($normalizedKey === null) {
                throw new DomainException('Format slot jam booking tidak valid.');
            }

            $matched = $slots->firstWhere('slot_key', $normalizedKey);

            if (! $matched) {
                // Check if user requested a slot earlier than now outside generated window
                $operatingDate = $dailyQueue->queue_date
                    ? $dailyQueue->queue_date->toDateString()
                    : $nowLocal->toDateString();

                $requestedStart = Carbon::parse($operatingDate.' '.$normalizedKey.':00', $timezone);
                if ($requestedStart->lte($nowLocal)) {
                    throw new DomainException('Slot jam yang dipilih sudah dimulai atau lewat. Pendaftaran tidak diperbolehkan pada slot yang sedang atau telah berjalan.');
                }

                throw new DomainException('Slot jam booking tidak tersedia dalam jadwal operasional hari ini.');
            }

            if ($matched['is_started']) {
                throw new DomainException('Slot jam '.$matched['slot_key'].' sudah dimulai. Silakan pilih slot waktu berikutnya yang masih tersedia.');
            }

            if ($matched['is_full']) {
                throw new DomainException('Slot jam '.$matched['slot_key'].' sudah penuh. Silakan pilih slot waktu lainnya.');
            }

            return [
                'start' => $matched['start_app'],
                'end' => $matched['end_app'],
                'slot_key' => $matched['slot_key'],
                'label' => $matched['label'],
            ];
        }

        $firstAvailable = $slots->firstWhere('is_available', true);
        if (! $firstAvailable) {
            throw new DomainException('Semua slot waktu antrean untuk hari ini sudah dimulai atau penuh.');
        }

        return [
            'start' => $firstAvailable['start_app'],
            'end' => $firstAvailable['end_app'],
            'slot_key' => $firstAvailable['slot_key'],
            'label' => $firstAvailable['label'],
        ];
    }

    /**
     * Automatically assign walk-in customers to the nearest upcoming available slot
     * (never an already-started or full slot).
     *
     * @return array{start: Carbon, end: Carbon, slot_key: string, label: string}
     *
     * @throws DomainException
     */
    public function resolveSlotForWalkIn(
        Business $business,
        Service $service,
        DailyQueue $dailyQueue
    ): array {
        $slots = $this->getSlotsForService($business, $service, $dailyQueue);
        $nearest = $slots->firstWhere('is_available', true);

        if (! $nearest) {
            throw new DomainException('Tidak ada slot waktu mendatang yang tersedia untuk pendaftaran walk-in hari ini.');
        }

        return [
            'start' => $nearest['start_app'],
            'end' => $nearest['end_app'],
            'slot_key' => $nearest['slot_key'],
            'label' => $nearest['label'],
        ];
    }

    /**
     * Build hourly slot windows from the business's today operating hours,
     * falling back to upcoming hourly windows when no schedule rows are configured.
     *
     * @return Collection<int, array{start: Carbon, end: Carbon}>
     */
    protected function buildSlotWindows(Business $business, Carbon $operatingDate, Carbon $nowLocal, string $timezone): Collection
    {
        $hasAnySchedule = $business->hours()->exists();

        if ($hasAnySchedule) {
            $todayHour = $business->hours()
                ->where('day_of_week', $operatingDate->dayOfWeek)
                ->first();

            if (! $todayHour || $todayHour->is_closed || ! $todayHour->open_time || ! $todayHour->close_time) {
                return collect();
            }

            $openLocal = Carbon::parse($operatingDate->toDateString().' '.$todayHour->open_time, $timezone);
            $closeLocal = Carbon::parse($operatingDate->toDateString().' '.$todayHour->close_time, $timezone);

            if ($closeLocal->lte($openLocal)) {
                return collect();
            }

            $windows = collect();
            $cursor = $openLocal->copy();

            while ($cursor->lt($closeLocal)) {
                $slotEnd = $cursor->copy()->addMinutes(self::SLOT_DURATION_MINUTES);
                if ($slotEnd->gt($closeLocal)) {
                    $slotEnd = $closeLocal->copy();
                }

                // Only include full or at-least-30-min slots
                if ($cursor->diffInMinutes($slotEnd) >= 30) {
                    $windows->push([
                        'start' => $cursor->copy(),
                        'end' => $slotEnd,
                    ]);
                }

                $cursor->addMinutes(self::SLOT_DURATION_MINUTES);
            }

            return $windows;
        }

        // Unconfigured schedule (e.g., minimal test fixtures): provide upcoming 8 hourly slots
        // anchored at the next whole hour so started-slot protection stays strictly enforced.
        $windows = collect();
        $firstUpcoming = $nowLocal->copy()->addHour()->startOfHour();

        for ($i = 0; $i < 8; $i++) {
            $start = $firstUpcoming->copy()->addHours($i);
            $windows->push([
                'start' => $start,
                'end' => $start->copy()->addMinutes(self::SLOT_DURATION_MINUTES),
            ]);
        }

        return $windows;
    }

    /**
     * Normalize requested slot string ('11:00' or ISO timestamp) to 'H:i' in business timezone.
     */
    protected function normalizeRequestedSlotKey(string $requestedSlot, string $timezone): ?string
    {
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $requestedSlot, $matches) === 1) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                return sprintf('%02d:%02d', $hour, $minute);
            }

            return null;
        }

        try {
            return Carbon::parse($requestedSlot)->setTimezone($timezone)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }
}

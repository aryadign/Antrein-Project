<?php

namespace App\Services;

use App\Models\Business;
use App\Models\QueueTicket;
use App\Models\Service;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;

class MerchantReportService
{
    /**
     * Minimum ticket threshold required to identify a peak hour (OQ-15).
     */
    public const int PEAK_HOUR_THRESHOLD = 50;

    /**
     * Generate comprehensive queue operational analytics for a merchant.
     *
     * @param  string  $period  'today', '7_days', '30_days', 'custom'
     * @param  string|null  $startDate  Y-m-d format for custom period
     * @param  string|null  $endDate  Y-m-d format for custom period
     * @param  bool  $includeTickets  Whether to attach full ticket records for the period (for export)
     * @return array<string, mixed>
     */
    public function getReportData(
        Business $business,
        string $period = '7_days',
        ?string $startDate = null,
        ?string $endDate = null,
        bool $includeTickets = false
    ): array {
        $timezone = $business->timezone ?: 'Asia/Makassar';
        $now = Carbon::now($timezone);

        [$localStart, $localEnd, $periodLabel] = $this->resolveDateRange($period, $startDate, $endDate, $now, $timezone);

        // Convert local calendar bounds to application/database timezone for exact query scoping
        $appTimezone = (string) config('app.timezone', 'Asia/Makassar');
        $dbStart = $localStart->copy()->startOfDay()->setTimezone($appTimezone);
        $dbEnd = $localEnd->copy()->endOfDay()->setTimezone($appTimezone);

        // Base scoped query for tickets created within the period
        $ticketsQuery = QueueTicket::query()
            ->where('business_id', $business->id)
            ->whereBetween('created_at', [$dbStart, $dbEnd]);

        $totalTickets = (clone $ticketsQuery)->count();

        // Separate status breakdown (M9-03)
        $completedCount = (clone $ticketsQuery)->where('status', QueueTicket::STATUS_SELESAI)->count();
        $skippedCount = (clone $ticketsQuery)->whereIn('status', [QueueTicket::STATUS_DILEWATI, QueueTicket::STATUS_TIDAK_HADIR])->count();
        $cancelledCount = (clone $ticketsQuery)->where('status', QueueTicket::STATUS_DIBATALKAN)->count();
        $waitingCount = (clone $ticketsQuery)->where('status', QueueTicket::STATUS_MENUNGGU)->count();
        $calledCount = (clone $ticketsQuery)->where('status', QueueTicket::STATUS_DIPANGGIL)->count();
        $servingCount = (clone $ticketsQuery)->where('status', QueueTicket::STATUS_SEDANG_DILAYANI)->count();
        $onlineCount = (clone $ticketsQuery)->where('source', QueueTicket::SOURCE_ONLINE)->count();
        $walkInCount = (clone $ticketsQuery)->where('source', QueueTicket::SOURCE_WALK_IN)->count();

        // 1. Average Waiting Time: created_at -> service_started_at (M9-04)
        $waitingTickets = (clone $ticketsQuery)
            ->whereNotNull('service_started_at')
            ->whereNotNull('created_at')
            ->get(['id', 'created_at', 'service_started_at']);

        $validWaitingDurations = [];
        foreach ($waitingTickets as $ticket) {
            if ($ticket->service_started_at >= $ticket->created_at) {
                $validWaitingDurations[] = $ticket->created_at->diffInSeconds($ticket->service_started_at);
            }
        }

        $waitingSamples = count($validWaitingDurations);
        $avgWaitingMinutes = $waitingSamples > 0
            ? round((array_sum($validWaitingDurations) / $waitingSamples) / 60, 1)
            : null;

        // 2. Average Service Duration: service_started_at -> completed_at on completed tickets (M9-05)
        $servingTickets = (clone $ticketsQuery)
            ->where('status', QueueTicket::STATUS_SELESAI)
            ->whereNotNull('service_started_at')
            ->whereNotNull('completed_at')
            ->get(['id', 'service_started_at', 'completed_at']);

        $validServingDurations = [];
        foreach ($servingTickets as $ticket) {
            if ($ticket->completed_at >= $ticket->service_started_at) {
                $validServingDurations[] = $ticket->service_started_at->diffInSeconds($ticket->completed_at);
            }
        }

        $serviceSamples = count($validServingDurations);
        $avgServiceMinutes = $serviceSamples > 0
            ? round((array_sum($validServingDurations) / $serviceSamples) / 60, 1)
            : null;

        // 3. Daily Ticket Trend Series for Chart.js (M9-06)
        [$trendLabels, $trendValues] = $this->buildDailyTrend($business->id, $localStart, $localEnd, $timezone);

        // 4. Status Distribution Data for Chart.js Donut (M9-07)
        $statusDistribution = [
            'menunggu' => $waitingCount,
            'dipanggil' => $calledCount,
            'sedang_dilayani' => $servingCount,
            'selesai' => $completedCount,
            'dilewati' => $skippedCount,
            'dibatalkan' => $cancelledCount,
        ];

        // 5. Per-Service Statistics & Gross Revenue (M9-08)
        $servicesBreakdown = $this->buildServicesBreakdown($business, $dbStart, $dbEnd);
        $grossRevenue = round((float) $servicesBreakdown->sum('gross_revenue'), 2);

        // 6. Peak-Hour Analysis (M9-09 & OQ-15)
        $peakHourData = $this->calculatePeakHour($business->id, $dbStart, $dbEnd, $timezone);

        // 7. Period Ticket Records (Included only when requested, e.g. for CSV/PDF export)
        $tickets = $includeTickets
            ? (clone $ticketsQuery)
                ->with(['service' => fn ($query) => $query->withTrashed()])
                ->orderBy('created_at', 'asc')
                ->get()
            : collect();

        return [
            'period' => $period,
            'periodLabel' => $periodLabel,
            'startDate' => $localStart->toDateString(),
            'endDate' => $localEnd->toDateString(),
            'timezone' => $timezone,
            'totalTickets' => $totalTickets,
            'completedCount' => $completedCount,
            'skippedCount' => $skippedCount,
            'cancelledCount' => $cancelledCount,
            'waitingCount' => $waitingCount,
            'calledCount' => $calledCount,
            'servingCount' => $servingCount,
            'onlineCount' => $onlineCount,
            'walkInCount' => $walkInCount,
            'grossRevenue' => $grossRevenue,
            'avgWaitingMinutes' => $avgWaitingMinutes,
            'waitingSamples' => $waitingSamples,
            'avgServiceMinutes' => $avgServiceMinutes,
            'serviceSamples' => $serviceSamples,
            'trendLabels' => $trendLabels,
            'trendValues' => $trendValues,
            'statusDistribution' => $statusDistribution,
            'servicesBreakdown' => $servicesBreakdown,
            'peakHour' => $peakHourData,
            'tickets' => $tickets,
            'hasData' => $totalTickets > 0,
        ];
    }

    /**
     * Build an organization-level summary by aggregating each branch's report.
     *
     * Each branch keeps its own timezone and tenant scope; totals are sums of the
     * already-scoped branch reports, never a cross-tenant raw ticket query.
     *
     * @param  Collection<int, Business>  $branches
     * @return array<string, mixed>
     */
    public function getOrganizationSummary(
        Collection $branches,
        string $period = '7_days',
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $rows = [];
        $totalTickets = 0;
        $completedCount = 0;
        $skippedCount = 0;
        $cancelledCount = 0;
        $waitingCount = 0;
        $calledCount = 0;
        $servingCount = 0;
        $onlineCount = 0;
        $walkInCount = 0;
        $totalGrossRevenue = 0.0;
        $waitingWeight = 0.0;
        $waitingSamples = 0;
        $serviceWeight = 0.0;
        $serviceSamples = 0;
        $trendLabels = [];
        $trendValues = [];
        $combinedHourlyBuckets = array_fill(0, 24, 0);

        $firstTimezone = $branches->first()?->timezone ?: 'Asia/Makassar';
        [$defaultLocalStart, $defaultLocalEnd, $defaultPeriodLabel] = $this->resolveDateRange(
            $period,
            $startDate,
            $endDate,
            Carbon::now($firstTimezone),
            $firstTimezone
        );
        $periodLabel = $defaultPeriodLabel;
        $resolvedStartDate = $defaultLocalStart->toDateString();
        $resolvedEndDate = $defaultLocalEnd->toDateString();

        $reviewsCount = 0;
        $ratingWeightedSum = 0.0;
        $ratingWeightedCount = 0;
        $openBranchesCount = 0;
        $suspendedBranchesCount = 0;

        foreach ($branches as $branch) {
            $report = $this->getReportData($branch, $period, $startDate, $endDate);

            $branchReviewsCount = (int) ($branch->reviews_count ?? $branch->customerReviews()->count());
            $branchAvgRatingRaw = $branch->getAttributes()['avg_rating'] ?? null;
            $branchAvgRating = $branchReviewsCount > 0
                ? round((float) ($branchAvgRatingRaw ?? $branch->customerReviews()->avg('rating') ?? 0), 1)
                : null;

            if ($branchReviewsCount > 0 && $branchAvgRating !== null) {
                $ratingWeightedSum += ($branchAvgRating * $branchReviewsCount);
                $ratingWeightedCount += $branchReviewsCount;
            }
            $reviewsCount += $branchReviewsCount;

            if ($branch->isOpen()) {
                $openBranchesCount++;
            }
            if ($branch->is_suspended) {
                $suspendedBranchesCount++;
            }

            $branchCompletionRate = $report['totalTickets'] > 0
                ? (int) round(($report['completedCount'] / $report['totalTickets']) * 100)
                : 0;
            $branchGrossRevenue = (float) ($report['grossRevenue'] ?? 0);

            $rows[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'branch_slug' => $branch->slug,
                'is_active' => (bool) $branch->is_active,
                'is_suspended' => (bool) $branch->is_suspended,
                'status' => $branch->status,
                'area_label' => $branch->areaLabel(),
                'active_counters_count' => (int) ($branch->active_counters_count ?? $branch->activeCounters()->count()),
                'active_services_count' => (int) ($branch->active_services_count ?? $branch->services()->count()),
                'reviews_count' => $branchReviewsCount,
                'avg_rating' => $branchAvgRating,
                'timezone' => $report['timezone'],
                'period_label' => $report['periodLabel'],
                'total_tickets' => $report['totalTickets'],
                'completed_count' => $report['completedCount'],
                'skipped_count' => $report['skippedCount'],
                'cancelled_count' => $report['cancelledCount'],
                'waiting_count' => $report['waitingCount'],
                'called_count' => $report['calledCount'],
                'serving_count' => $report['servingCount'],
                'online_count' => $report['onlineCount'],
                'walk_in_count' => $report['walkInCount'],
                'gross_revenue' => $branchGrossRevenue,
                'completion_rate' => $branchCompletionRate,
                'avg_waiting_minutes' => $report['avgWaitingMinutes'],
                'avg_service_minutes' => $report['avgServiceMinutes'],
                'has_data' => $report['hasData'],
            ];

            $totalTickets += $report['totalTickets'];
            $completedCount += $report['completedCount'];
            $skippedCount += $report['skippedCount'];
            $cancelledCount += $report['cancelledCount'];
            $waitingCount += $report['waitingCount'];
            $calledCount += $report['calledCount'];
            $servingCount += $report['servingCount'];
            $onlineCount += $report['onlineCount'];
            $walkInCount += $report['walkInCount'];
            $totalGrossRevenue += $branchGrossRevenue;

            if ($report['avgWaitingMinutes'] !== null && $report['waitingSamples'] > 0) {
                $waitingWeight += $report['avgWaitingMinutes'] * $report['waitingSamples'];
                $waitingSamples += $report['waitingSamples'];
            }

            if ($report['avgServiceMinutes'] !== null && $report['serviceSamples'] > 0) {
                $serviceWeight += $report['avgServiceMinutes'] * $report['serviceSamples'];
                $serviceSamples += $report['serviceSamples'];
            }

            if (empty($trendLabels)) {
                $trendLabels = $report['trendLabels'];
                $trendValues = $report['trendValues'];
            } else {
                foreach ($report['trendValues'] as $idx => $val) {
                    if (array_key_exists($idx, $trendValues)) {
                        $trendValues[$idx] += $val;
                    }
                }
            }

            $branchBuckets = $report['peakHour']['hourly_buckets'] ?? [];
            foreach ($branchBuckets as $hour => $bucketCount) {
                $combinedHourlyBuckets[$hour] = ($combinedHourlyBuckets[$hour] ?? 0) + $bucketCount;
            }
        }

        $maxHourCount = 0;
        $maxHour = null;
        foreach ($combinedHourlyBuckets as $hour => $count) {
            if ($count > $maxHourCount) {
                $maxHourCount = $count;
                $maxHour = $hour;
            }
        }
        $peakThresholdMet = $maxHourCount >= self::PEAK_HOUR_THRESHOLD;

        return [
            'period' => $period,
            'periodLabel' => $periodLabel,
            'startDate' => $resolvedStartDate,
            'endDate' => $resolvedEndDate,
            'timezone' => $firstTimezone,
            'rows' => $rows,
            'totals' => [
                'branches' => count($rows),
                'open_branches_count' => $openBranchesCount,
                'suspended_branches_count' => $suspendedBranchesCount,
                'total_tickets' => $totalTickets,
                'completed_count' => $completedCount,
                'skipped_count' => $skippedCount,
                'cancelled_count' => $cancelledCount,
                'waiting_count' => $waitingCount,
                'called_count' => $calledCount,
                'serving_count' => $servingCount,
                'online_count' => $onlineCount,
                'walk_in_count' => $walkInCount,
                'gross_revenue' => round($totalGrossRevenue, 2),
                'completion_rate' => $totalTickets > 0 ? (int) round(($completedCount / $totalTickets) * 100) : 0,
                'reviews_count' => $reviewsCount,
                'avg_rating' => $ratingWeightedCount > 0 ? round($ratingWeightedSum / $ratingWeightedCount, 1) : null,
                'avg_waiting_minutes' => $waitingSamples > 0
                    ? round($waitingWeight / $waitingSamples, 1)
                    : null,
                'waiting_samples' => $waitingSamples,
                'avg_service_minutes' => $serviceSamples > 0
                    ? round($serviceWeight / $serviceSamples, 1)
                    : null,
                'service_samples' => $serviceSamples,
            ],
            'trendLabels' => $trendLabels,
            'trendValues' => $trendValues,
            'statusDistribution' => [
                'menunggu' => $waitingCount,
                'dipanggil' => $calledCount,
                'sedang_dilayani' => $servingCount,
                'selesai' => $completedCount,
                'dilewati' => $skippedCount,
                'dibatalkan' => $cancelledCount,
            ],
            'peakHour' => [
                'threshold_met' => $peakThresholdMet,
                'threshold' => self::PEAK_HOUR_THRESHOLD,
                'max_count' => $maxHourCount,
                'max_hour' => $maxHour,
                'peak_range' => ($maxHour !== null && $peakThresholdMet)
                    ? sprintf('%02d:00 – %02d:00', $maxHour, ($maxHour + 1) % 24)
                    : null,
                'top_hour_formatted' => $maxHour !== null
                    ? sprintf('%02d:00 – %02d:00', $maxHour, ($maxHour + 1) % 24)
                    : null,
            ],
            'hasData' => $totalTickets > 0,
        ];
    }

    /**
     * Resolve the calendar start and end dates based on user selection in local business timezone.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     *
     * @throws DomainException
     */
    protected function resolveDateRange(
        string $period,
        ?string $startDate,
        ?string $endDate,
        Carbon $now,
        string $timezone
    ): array {
        if ($period === 'today') {
            return [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                'Hari Ini ('.$now->translatedFormat('d M Y').')',
            ];
        }

        if ($period === '30_days') {
            return [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
                '30 Hari Terakhir',
            ];
        }

        if ($period === 'custom') {
            if (! $startDate || ! $endDate) {
                throw new DomainException('Rentang tanggal khusus wajib menyertakan tanggal mulai dan tanggal selesai.');
            }

            try {
                $start = Carbon::createFromFormat('Y-m-d', $startDate, $timezone)->startOfDay();
                $end = Carbon::createFromFormat('Y-m-d', $endDate, $timezone)->endOfDay();
            } catch (\Throwable) {
                throw new DomainException('Format tanggal tidak valid. Gunakan format YYYY-MM-DD.');
            }

            if ($start->gt($end)) {
                throw new DomainException('Tanggal mulai tidak boleh lebih besar daripada tanggal selesai.');
            }

            return [
                $start,
                $end,
                $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y'),
            ];
        }

        // Default: '7_days'
        return [
            $now->copy()->subDays(6)->startOfDay(),
            $now->copy()->endOfDay(),
            '7 Hari Terakhir',
        ];
    }

    /**
     * Build daily ticket count series grouped by local calendar date for Chart.js.
     *
     * @return array{0: list<string>, 1: list<int>}
     */
    protected function buildDailyTrend(
        int $businessId,
        Carbon $localStart,
        Carbon $localEnd,
        string $timezone
    ): array {
        $labels = [];
        $values = [];
        $appTimezone = (string) config('app.timezone', 'Asia/Makassar');

        $diffDays = $localStart->diffInDays($localEnd);
        // Cap daily chart points to 90 days to maintain snappy rendering
        $daysCount = min($diffDays + 1, 90);

        for ($i = 0; $i < $daysCount; $i++) {
            $currentDay = $localStart->copy()->addDays($i);
            $dayDbStart = $currentDay->copy()->startOfDay()->setTimezone($appTimezone);
            $dayDbEnd = $currentDay->copy()->endOfDay()->setTimezone($appTimezone);

            $count = QueueTicket::where('business_id', $businessId)
                ->whereBetween('created_at', [$dayDbStart, $dayDbEnd])
                ->count();

            $labels[] = $currentDay->translatedFormat('d M');
            $values[] = $count;
        }

        return [$labels, $values];
    }

    /**
     * Compute performance metrics grouped by business service (M9-08).
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildServicesBreakdown(Business $business, Carbon $dbStart, Carbon $dbEnd): Collection
    {
        // Retrieve all services of this business (active, inactive, and soft-deleted with historical data)
        $services = Service::withTrashed()
            ->where('business_id', $business->id)
            ->orderBy('name')
            ->get();

        return $services->map(function (Service $service) use ($business, $dbStart, $dbEnd) {
            $serviceTickets = QueueTicket::where('business_id', $business->id)
                ->where('service_id', $service->id)
                ->whereBetween('created_at', [$dbStart, $dbEnd])
                ->get();

            $total = $serviceTickets->count();
            if ($service->trashed() && $total === 0) {
                return null;
            }

            $completed = $serviceTickets->where('status', QueueTicket::STATUS_SELESAI)->count();
            $skipped = $serviceTickets->whereIn('status', [QueueTicket::STATUS_DILEWATI, QueueTicket::STATUS_TIDAK_HADIR])->count();
            $cancelled = $serviceTickets->where('status', QueueTicket::STATUS_DIBATALKAN)->count();

            $unitPrice = (float) ($service->price ?? 0);
            $grossRevenue = round($completed * $unitPrice, 2);

            // Calculate average service duration for this specific service
            $validDurations = [];
            foreach ($serviceTickets->where('status', QueueTicket::STATUS_SELESAI) as $ticket) {
                if ($ticket->service_started_at && $ticket->completed_at && $ticket->completed_at >= $ticket->service_started_at) {
                    $validDurations[] = $ticket->service_started_at->diffInSeconds($ticket->completed_at);
                }
            }

            $avgDuration = count($validDurations) > 0
                ? round((array_sum($validDurations) / count($validDurations)) / 60, 1)
                : null;

            return [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'is_active' => $service->is_active && ! $service->trashed(),
                'unit_price' => $unitPrice,
                'total_tickets' => $total,
                'completed_tickets' => $completed,
                'skipped_tickets' => $skipped,
                'cancelled_tickets' => $cancelled,
                'gross_revenue' => $grossRevenue,
                'avg_service_minutes' => $avgDuration,
            ];
        })->filter()->values();
    }

    /**
     * Compute peak-hour insight based on OQ-15 specifications.
     * Bucket: 1-hour interval in local timezone, excluding cancelled tickets.
     * Threshold: At least 50 tickets in a single 1-hour bucket.
     *
     * @return array<string, mixed>
     */
    protected function calculatePeakHour(
        int $businessId,
        Carbon $dbStart,
        Carbon $dbEnd,
        string $timezone
    ): array {
        $nonCancelledTickets = QueueTicket::where('business_id', $businessId)
            ->where('status', '!=', QueueTicket::STATUS_DIBATALKAN)
            ->whereBetween('created_at', [$dbStart, $dbEnd])
            ->get(['id', 'created_at']);

        $hourlyBuckets = array_fill(0, 24, 0);

        foreach ($nonCancelledTickets as $ticket) {
            $localHour = (int) $ticket->created_at->timezone($timezone)->format('H');
            $hourlyBuckets[$localHour]++;
        }

        $maxCount = 0;
        $maxHour = null;

        foreach ($hourlyBuckets as $hour => $count) {
            if ($count > $maxCount) {
                $maxCount = $count;
                $maxHour = $hour;
            }
        }

        $thresholdMet = $maxCount >= self::PEAK_HOUR_THRESHOLD;

        return [
            'threshold_met' => $thresholdMet,
            'threshold' => self::PEAK_HOUR_THRESHOLD,
            'max_count' => $maxCount,
            'max_hour' => $maxHour,
            'peak_range' => ($maxHour !== null && $thresholdMet)
                ? sprintf('%02d:00 – %02d:00', $maxHour, ($maxHour + 1) % 24)
                : null,
            'top_hour_formatted' => $maxHour !== null
                ? sprintf('%02d:00 – %02d:00', $maxHour, ($maxHour + 1) % 24)
                : null,
            'hourly_buckets' => $hourlyBuckets,
        ];
    }
}

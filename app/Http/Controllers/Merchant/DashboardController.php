<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\QueueTicket;
use App\Services\MerchantReportService;
use App\Services\QueueService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected QueueService $queueService,
        protected MerchantReportService $reportService
    ) {}

    /**
     * Display the merchant operational dashboard overview.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        /** @var Business|null $business */
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding');
        }

        $timezone = $business->timezone ?: 'Asia/Makassar';
        $operatingDate = Carbon::now($timezone)->toDateString();
        $this->queueService->expireStaleCalledTickets($business);
        $dailyQueue = $this->queueService->getOrCreateDailyQueue($business, $operatingDate);

        $isOwner = $user->isOwner();
        $isOperator = $user->isOperator();

        // Owner Beranda renders Laporan-style combined multi-branch analytics across period filter
        $organization = $user->primaryOrganization();
        $isCombinedScope = $isOwner;
        $orgReport = null;
        $organizationTotals = null;

        if ($isCombinedScope) {
            $period = (string) $request->query('period', '7_days');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            if (! in_array($period, ['today', '7_days', '30_days', 'custom'], true)) {
                $period = '7_days';
            }

            $orgBranches = $organization
                ? $organization->branches()
                    ->with('category')
                    ->withCount(['activeCounters as active_counters_count', 'services as active_services_count', 'customerReviews as reviews_count'])
                    ->withAvg('customerReviews as avg_rating', 'rating')
                    ->orderByDesc('is_active')
                    ->orderBy('name')
                    ->get()
                : $user->accessibleBusinesses()
                    ->load('category')
                    ->loadCount(['activeCounters as active_counters_count', 'services as active_services_count', 'customerReviews as reviews_count'])
                    ->loadAvg('customerReviews as avg_rating', 'rating');

            try {
                $orgReport = $this->reportService->getOrganizationSummary(
                    branches: $orgBranches,
                    period: $period,
                    startDate: $startDate ? (string) $startDate : null,
                    endDate: $endDate ? (string) $endDate : null
                );
            } catch (DomainException $e) {
                return redirect()->route('merchant.dashboard', ['period' => '7_days'])
                    ->withErrors(['date_range' => $e->getMessage()]);
            }

            $organizationTotals = $orgReport['totals'];
        }

        // Calculate single-branch live queue metrics for Operator desk
        $stats = $isOperator ? [
            'total' => QueueTicket::where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->count(),
            'menunggu' => QueueTicket::where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', QueueTicket::STATUS_MENUNGGU)
                ->count(),
            'dipanggil' => QueueTicket::where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', QueueTicket::STATUS_DIPANGGIL)
                ->count(),
            'sedang_dilayani' => QueueTicket::where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', QueueTicket::STATUS_SEDANG_DILAYANI)
                ->count(),
            'selesai' => QueueTicket::where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', QueueTicket::STATUS_SELESAI)
                ->count(),
            'tidak_hadir' => QueueTicket::where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', QueueTicket::STATUS_TIDAK_HADIR)
                ->count(),
            'dilewati' => QueueTicket::where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', QueueTicket::STATUS_DILEWATI)
                ->count(),
            'dibatalkan' => QueueTicket::where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', QueueTicket::STATUS_DIBATALKAN)
                ->count(),
        ] : [
            'total' => 0,
            'menunggu' => 0,
            'dipanggil' => 0,
            'sedang_dilayani' => 0,
            'selesai' => 0,
            'tidak_hadir' => 0,
            'dilewati' => 0,
            'dibatalkan' => 0,
        ];

        // User's operational counters (Operator only)
        $activeCountersStatus = $isOperator ? $this->queueService->getActiveCountersStatus($business) : collect();
        if (! $isOperator) {
            $availableCounters = collect();
            $selectedCounter = null;
        } elseif (! $business->usesStations()) {
            $primaryCounter = $activeCountersStatus->first();
            $availableCounters = $primaryCounter ? collect([$primaryCounter]) : collect();
            $selectedCounter = $primaryCounter;
        } else {
            $availableCounters = $user->assignedCounters();
            $selectedCounterId = session('merchant_active_counter_id') ?: ($availableCounters->first()?->id);
            $selectedCounter = $availableCounters->firstWhere('id', $selectedCounterId) ?: $availableCounters->first();
        }

        // Active ticket currently being called or served on the selected counter (Operator only)
        $nowServing = $isOperator ? $this->queueService->getCurrentServingTicket($business, $selectedCounter) : null;

        // Fetch up to 5 active tickets in FIFO order for preview (Operator only)
        $activeTickets = $isOperator
            ? QueueTicket::with(['service', 'counter'])
                ->where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->whereIn('status', [
                    QueueTicket::STATUS_MENUNGGU,
                    QueueTicket::STATUS_DIPANGGIL,
                    QueueTicket::STATUS_SEDANG_DILAYANI,
                ])
                ->orderByRaw('CASE WHEN booking_slot_start IS NULL THEN 0 ELSE 1 END ASC')
                ->orderBy('booking_slot_start', 'asc')
                ->orderBy('sequence_number', 'asc')
                ->limit(5)
                ->get()
            : collect();

        // Active services for walk-in modal & setup checklist
        $services = $business->services()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Onboarding / setup readiness checklist
        $profileComplete = ! empty($business->name) && ! empty($business->category_id) && ! empty($business->phone);
        $hoursConfigured = $business->hours()->where('is_closed', false)->exists();
        $servicesConfigured = $services->isNotEmpty();
        $isSetupComplete = $profileComplete && $hoursConfigured && $servicesConfigured;

        return view('merchant.dashboard', [
            'business' => $business,
            'dailyQueue' => $dailyQueue,
            'stats' => $stats,
            'nowServing' => $nowServing,
            'activeTickets' => $activeTickets,
            'services' => $services,
            'operatingDate' => $operatingDate,
            'timezone' => $timezone,
            'profileComplete' => $profileComplete,
            'hoursConfigured' => $hoursConfigured,
            'servicesConfigured' => $servicesConfigured,
            'isSetupComplete' => $isSetupComplete,
            'availableCounters' => $availableCounters,
            'selectedCounter' => $selectedCounter,
            'activeCountersStatus' => $activeCountersStatus,
            'isOwner' => $isOwner,
            'isOperator' => $isOperator,
            'isCombinedScope' => $isCombinedScope,
            'organization' => $organization,
            'orgReport' => $orgReport,
            'organizationTotals' => $organizationTotals,
        ]);
    }
}

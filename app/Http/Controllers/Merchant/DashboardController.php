<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\QueueTicket;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected QueueService $queueService
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
        $dailyQueue = $this->queueService->getOrCreateDailyQueue($business, $operatingDate);

        // Calculate queue metrics for today
        $stats = [
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
            'dilewati' => QueueTicket::where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', QueueTicket::STATUS_DILEWATI)
                ->count(),
            'dibatalkan' => QueueTicket::where('business_id', $business->id)
                ->where('daily_queue_id', $dailyQueue->id)
                ->where('status', QueueTicket::STATUS_DIBATALKAN)
                ->count(),
        ];

        // User's operational counters
        $availableCounters = $user->assignedCounters();
        $selectedCounterId = session('merchant_active_counter_id') ?: ($availableCounters->first()?->id);
        $selectedCounter = $availableCounters->firstWhere('id', $selectedCounterId) ?: $availableCounters->first();

        // Active ticket currently being called or served on the selected counter (or overall)
        $nowServing = $this->queueService->getCurrentServingTicket($business, $selectedCounter);
        $activeCountersStatus = $this->queueService->getActiveCountersStatus($business);

        // Fetch up to 5 active tickets in FIFO order for preview (menunggu, dipanggil, sedang_dilayani)
        $activeTickets = QueueTicket::with(['service', 'counter'])
            ->where('business_id', $business->id)
            ->where('daily_queue_id', $dailyQueue->id)
            ->whereIn('status', [
                QueueTicket::STATUS_MENUNGGU,
                QueueTicket::STATUS_DIPANGGIL,
                QueueTicket::STATUS_SEDANG_DILAYANI,
            ])
            ->orderBy('sequence_number', 'asc')
            ->limit(5)
            ->get();

        // Active services for walk-in modal
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
        ]);
    }
}


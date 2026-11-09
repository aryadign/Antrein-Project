<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\QueueTicket;
use App\Services\QrCodeService;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function __construct(
        protected QueueService $queueService,
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Display the operational queue list and history with filtering and search.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        $timezone = $business->timezone ?: 'Asia/Makassar';
        $operatingDate = Carbon::now($timezone)->toDateString();
        $dailyQueue = $this->queueService->getOrCreateDailyQueue($business, $operatingDate);

        // Filters and search inputs
        $selectedDate = $request->query('date', $operatingDate);
        $selectedStatus = $request->query('status');
        $selectedService = $request->query('service_id');
        $selectedSource = $request->query('source');
        $search = trim((string) $request->query('search', ''));

        $query = QueueTicket::with(['service', 'statusHistories'])
            ->where('business_id', $business->id);

        // Date period filter
        if ($selectedDate !== 'all' && $selectedDate !== '') {
            $query->whereHas('dailyQueue', function ($q) use ($selectedDate) {
                $q->whereDate('queue_date', $selectedDate);
            });
        }

        // Status filter
        if ($selectedStatus && in_array($selectedStatus, [
            QueueTicket::STATUS_MENUNGGU,
            QueueTicket::STATUS_DIPANGGIL,
            QueueTicket::STATUS_SEDANG_DILAYANI,
            QueueTicket::STATUS_SELESAI,
            QueueTicket::STATUS_DILEWATI,
            QueueTicket::STATUS_DIBATALKAN,
        ])) {
            $query->where('status', $selectedStatus);
        }

        // Service filter
        if ($selectedService) {
            $query->where('service_id', (int) $selectedService);
        }

        // Source filter
        if ($selectedSource && in_array($selectedSource, ['online', 'walk_in'])) {
            $query->where('source', $selectedSource);
        }

        // Search by ticket number or customer name
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Smart ordering: for current day active queue, prioritize active tickets, else latest
        if ($selectedDate === $operatingDate) {
            $query->orderByRaw("CASE 
                WHEN status = 'sedang_dilayani' THEN 1 
                WHEN status = 'dipanggil' THEN 2 
                WHEN status = 'menunggu' THEN 3 
                ELSE 4 END ASC, sequence_number ASC");
        } else {
            $query->latest('id');
        }

        $tickets = $query->paginate(15)->withQueryString();

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

        // Now serving ticket
        $nowServing = $this->queueService->getCurrentServingTicket($business);

        // Active services for walk-in modal and filter dropdown
        $services = $business->services()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('merchant.queues.index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'nowServing' => $nowServing,
            'services' => $services,
            'business' => $business,
            'dailyQueue' => $dailyQueue,
            'operatingDate' => $operatingDate,
            'selectedDate' => $selectedDate,
            'selectedStatus' => $selectedStatus,
            'selectedService' => $selectedService,
            'selectedSource' => $selectedSource,
            'search' => $search,
        ]);
    }

    /**
     * Display the details and immutable status audit history of a specific ticket.
     */
    public function show(Request $request, QueueTicket $ticket): View
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($ticket->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak akses terhadap tiket antrean usaha lain.');
        }

        $ticket->load([
            'service',
            'dailyQueue',
            'statusHistories.actor',
            'business',
        ]);

        return view('merchant.queues.show', [
            'ticket' => $ticket,
            'business' => $business,
        ]);
    }

    /**
     * Display the public queue URL and downloadable QR Code.
     */
    public function qr(Request $request): View
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        $publicUrl = route('customer.queue', ['slug' => $business->slug], true);
        $qrSvg = $this->qrCodeService->generateSvg($publicUrl, 280);

        return view('merchant.queues.qr', [
            'business' => $business,
            'publicUrl' => $publicUrl,
            'qrSvg' => $qrSvg,
        ]);
    }
}

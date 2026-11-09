<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Services\QueueService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QueueTicketController extends Controller
{
    public function __construct(
        protected QueueService $queueService
    ) {}

    /**
     * Display the public UMKM page with service catalog and intake options.
     */
    public function show(Request $request, string $slug): JsonResponse|View
    {
        /** @var Business $business */
        $business = Business::where('slug', $slug)
            ->with(['category', 'hours'])
            ->firstOrFail();

        $dailyQueue = $this->queueService->getOrCreateDailyQueue($business);

        $services = $business->services()->active()->get();

        $servicesWithQuota = $services->map(function (Service $service) use ($dailyQueue) {
            $remaining = $service->getRemainingQuotaForDailyQueue($dailyQueue);
            $service->remaining_quota = $remaining;
            $service->is_quota_full = ($service->daily_quota !== null && $remaining !== null && $remaining <= 0);

            return $service;
        });

        $servingTicket = $this->queueService->getCurrentServingTicket($business);

        $waitingCount = QueueTicket::where('business_id', $business->id)
            ->where('daily_queue_id', $dailyQueue->id)
            ->where('status', QueueTicket::STATUS_MENUNGGU)
            ->count();

        // Resolve today's schedule based on business timezone
        $timezone = $business->timezone ?: 'Asia/Makassar';
        $todayDayOfWeek = (int) Carbon::now($timezone)->dayOfWeek; // 0 = Minggu, 6 = Sabtu
        $todayHour = $business->hours->firstWhere('day_of_week', $todayDayOfWeek);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'business' => [
                        'id' => $business->id,
                        'name' => $business->name,
                        'slug' => $business->slug,
                        'description' => $business->description,
                        'category' => $business->category?->name,
                        'address' => $business->address,
                        'phone' => $business->phone,
                        'status' => $business->status,
                        'is_open' => $business->isOpen(),
                        'is_paused' => $business->isPaused(),
                        'is_closed' => $business->isClosed(),
                        'is_suspended' => (bool) $business->is_suspended,
                        'timezone' => $business->timezone,
                        'today_schedule' => $todayHour ? $todayHour->getFormattedSchedule() : null,
                    ],
                    'services' => $servicesWithQuota->map(fn (Service $s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'description' => $s->description,
                        'duration_minutes' => $s->duration_minutes,
                        'price' => $s->price,
                        'daily_quota' => $s->daily_quota,
                        'remaining_quota' => $s->remaining_quota,
                        'is_quota_full' => $s->is_quota_full,
                    ]),
                    'now_serving' => $servingTicket?->ticket_number,
                    'waiting_count' => $waitingCount,
                ],
            ]);
        }

        return view('customer.show', [
            'business' => $business,
            'services' => $servicesWithQuota,
            'dailyQueue' => $dailyQueue,
            'servingTicket' => $servingTicket,
            'waitingCount' => $waitingCount,
            'todayHour' => $todayHour,
        ]);
    }

    /**
     * Display the dedicated online queue intake form.
     */
    public function create(Request $request, string $slug): View
    {
        /** @var Business $business */
        $business = Business::where('slug', $slug)
            ->with(['category', 'hours'])
            ->firstOrFail();

        $dailyQueue = $this->queueService->getOrCreateDailyQueue($business);

        $services = $business->services()->active()->get()->map(function (Service $service) use ($dailyQueue) {
            $remaining = $service->getRemainingQuotaForDailyQueue($dailyQueue);
            $service->remaining_quota = $remaining;
            $service->is_quota_full = ($service->daily_quota !== null && $remaining !== null && $remaining <= 0);

            return $service;
        });

        $selectedServiceId = (int) $request->query('service_id');
        $idempotencyKey = (string) Str::uuid();

        return view('customer.form', [
            'business' => $business,
            'services' => $services,
            'selectedServiceId' => $selectedServiceId,
            'idempotencyKey' => $idempotencyKey,
        ]);
    }

    /**
     * Handle public online queue ticket creation.
     */
    public function store(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        // 1. IP Rate Limiting: 30 requests per 15 minutes per IP
        $throttleKey = 'queue-take-ip:'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak permintaan pendaftaran antrean. Silakan coba kembali dalam beberapa menit.',
                    'retry_after' => $seconds,
                ], 429, ['Retry-After' => $seconds]);
            }

            throw ValidationException::withMessages([
                'rate_limit' => 'Terlalu banyak permintaan pendaftaran antrean. Silakan coba kembali dalam beberapa menit.',
            ]);
        }
        RateLimiter::hit($throttleKey, 900); // 15 minutes = 900 seconds

        // 2. Validate Business
        /** @var Business $business */
        $business = Business::where('slug', $slug)->firstOrFail();

        // 3. Validate Inputs
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        /** @var Service $service */
        $service = Service::where('id', $validated['service_id'])
            ->where('business_id', $business->id)
            ->firstOrFail();

        $activeSessionTicketIds = session()->get('customer_ticket_ids', []);

        try {
            $ticket = $this->queueService->generateOnlineTicket(
                business: $business,
                service: $service,
                customerName: $validated['customer_name'],
                customerPhone: $validated['customer_phone'] ?? null,
                idempotencyKey: $validated['idempotency_key'] ?? null,
                activeSessionTicketIds: is_array($activeSessionTicketIds) ? $activeSessionTicketIds : []
            );

            // Record this ticket ID into customer browser session for session protection
            session()->push('customer_ticket_ids', $ticket->id);

            $queuePosition = $this->queueService->calculateQueuePosition($ticket);
            $estimatedWaitMinutes = max(0, ($queuePosition - 1) * ($service->duration_minutes ?? 15));

            $maskedPhone = null;
            if ($ticket->customer_phone) {
                $len = strlen($ticket->customer_phone);
                $maskedPhone = $len > 6
                    ? substr($ticket->customer_phone, 0, 3).'***'.substr($ticket->customer_phone, -3)
                    : '***';
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tiket antrean online berhasil dibuat.',
                    'data' => [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'sequence_number' => $ticket->sequence_number,
                        'access_token' => $ticket->access_token,
                        'tracking_token' => $ticket->access_token,
                        'customer_name' => $ticket->customer_name,
                        'customer_phone_masked' => $maskedPhone,
                        'service_name' => $service->name,
                        'status' => $ticket->status,
                        'queue_position' => $queuePosition,
                        'estimated_wait_minutes' => $estimatedWaitMinutes,
                        'tracking_url' => route('customer.queue.track', ['token' => $ticket->access_token]),
                    ],
                ], 201);
            }

            return redirect()->route('customer.queue.track', ['token' => $ticket->access_token])
                ->with('status', 'Tiket antrean Anda berhasil dibuat!');
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['queue_error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Public ticket tracking lookup via secure access token.
     */
    public function track(Request $request, string $token): JsonResponse|Response|View
    {
        /** @var QueueTicket $ticket */
        $ticket = QueueTicket::where('access_token', $token)
            ->with(['business.category', 'business.hours', 'service'])
            ->firstOrFail();

        $isExpired = $this->queueService->isTokenExpired($ticket);

        if ($isExpired) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'expired',
                    'message' => 'Tiket antrean ini telah kedaluwarsa dan tidak lagi dapat dilacak.',
                ], 410);
            }

            return response()->view('customer.expired', [
                'business' => $ticket->business,
                'isExpired' => true,
            ], 410);
        }

        $position = $this->queueService->calculateQueuePosition($ticket);
        $aheadCount = max(0, $position - 1);
        $servingTicket = $this->queueService->getCurrentServingTicket($ticket->business);

        // Mask phone number for privacy
        $maskedPhone = null;
        if ($ticket->customer_phone) {
            $phone = $ticket->customer_phone;
            $len = strlen($phone);
            $maskedPhone = $len > 6
                ? substr($phone, 0, 3).'***'.substr($phone, -3)
                : '***';
        }

        $estimatedWaitMinutes = max(0, ($position - 1) * ($ticket->service->duration_minutes ?? 15));

        $data = [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'sequence_number' => $ticket->sequence_number,
            'customer_name' => $ticket->customer_name,
            'customer_phone_masked' => $maskedPhone,
            'customer_phone' => $maskedPhone,
            'status' => $ticket->status,
            'source' => $ticket->source,
            'queue_position' => $position,
            'ahead_count' => $aheadCount,
            'estimated_wait_minutes' => $estimatedWaitMinutes,
            'service_name' => $ticket->service->name,
            'business_name' => $ticket->business->name,
            'service' => [
                'name' => $ticket->service->name,
                'duration_minutes' => $ticket->service->duration_minutes,
                'price' => $ticket->service->price,
            ],
            'business' => [
                'name' => $ticket->business->name,
                'slug' => $ticket->business->slug,
            ],
            'now_serving' => $servingTicket ? $servingTicket->ticket_number : null,
            'called_at' => $ticket->called_at?->toIso8601String(),
            'service_started_at' => $ticket->service_started_at?->toIso8601String(),
            'completed_at' => $ticket->completed_at?->toIso8601String(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('customer.track', [
            'ticket' => $ticket,
            'business' => $ticket->business,
            'service' => $ticket->service,
            'data' => $data,
            'position' => $position,
            'aheadCount' => $aheadCount,
            'servingTicket' => $servingTicket,
            'maskedPhone' => $maskedPhone,
            'estimatedWaitMinutes' => $estimatedWaitMinutes,
            'isExpired' => false,
        ]);
    }
}

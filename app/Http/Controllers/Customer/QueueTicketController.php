<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Services\QrCodeService;
use App\Services\QueueService;
use App\Services\WaitingTimeEstimationService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QueueTicketController extends Controller
{
    public function __construct(
        protected QueueService $queueService,
        protected WaitingTimeEstimationService $waitingTimeEstimationService,
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Display the public UMKM page with service catalog, sibling branches, and Shopee-style reviews.
     */
    public function show(Request $request, string $slug): JsonResponse|View
    {
        /** @var Business $business */
        $business = Business::where('slug', $slug)
            ->with(['category', 'hours', 'organization'])
            ->withAvg('customerReviews as reviews_avg_rating', 'rating')
            ->withCount('customerReviews as reviews_count')
            ->withCount('activeCounters as active_counters_count')
            ->withAvg(['services as avg_service_minutes' => fn ($q) => $q->where('is_active', true)], 'duration_minutes')
            ->withMin(['services as min_service_minutes' => fn ($q) => $q->where('is_active', true)], 'duration_minutes')
            ->withMax(['services as max_service_minutes' => fn ($q) => $q->where('is_active', true)], 'duration_minutes')
            ->firstOrFail();

        abort_unless($business->isApproved(), 404);

        // Opportunistically expire 15-minute no-show calls so queue waiting count stays accurate
        $this->queueService->expireStaleCalledTickets($business);

        $dailyQueue = $this->queueService->getOrCreateDailyQueue($business);

        $services = $business->services()->active()->get();

        $servicesWithQuota = $services->map(function (Service $service) use ($dailyQueue) {
            $remaining = $service->getRemainingQuotaForDailyQueue($dailyQueue);
            $service->remaining_quota = $remaining;
            $service->is_quota_full = ($service->daily_quota !== null && $remaining !== null && $remaining <= 0);

            return $service;
        });

        $waitingCount = QueueTicket::where('business_id', $business->id)
            ->where('daily_queue_id', $dailyQueue->id)
            ->where('status', QueueTicket::STATUS_MENUNGGU)
            ->count();

        $waitRange = $business->customerWaitRange($waitingCount);
        $activeTicketCount = $request->user()?->activeTicketsCount() ?? 0;

        // Shopee-style review breakdown & filtering
        $totalReviewsCount = (int) ($business->reviews_count ?? 0);
        $rawRatingCounts = $business->customerReviews()
            ->selectRaw('rating, COUNT(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');

        $ratingBreakdown = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = (int) ($rawRatingCounts->get($star) ?? 0);
            $ratingBreakdown[$star] = [
                'count' => $count,
                'percent' => $totalReviewsCount > 0 ? (int) round(($count / $totalReviewsCount) * 100) : 0,
            ];
        }

        $reviewsWithCommentCount = $business->customerReviews()
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->count();

        $selectedStar = $request->integer('bintang');
        if ($selectedStar < 1 || $selectedStar > 5) {
            $selectedStar = null;
        }
        $withCommentOnly = $request->boolean('dengan_komentar');
        $reviewSort = (string) $request->query('urut_ulasan', 'terbaru');
        if (! in_array($reviewSort, ['terbaru', 'rating_tinggi', 'rating_rendah'], true)) {
            $reviewSort = 'terbaru';
        }

        $reviewsQuery = $business->customerReviews()->with('service:id,name');
        if ($selectedStar !== null) {
            $reviewsQuery->where('rating', $selectedStar);
        }
        if ($withCommentOnly) {
            $reviewsQuery->whereNotNull('comment')->where('comment', '!=', '');
        }
        match ($reviewSort) {
            'rating_tinggi' => $reviewsQuery->orderByDesc('rating')->latest(),
            'rating_rendah' => $reviewsQuery->orderBy('rating')->latest(),
            default => $reviewsQuery->latest(),
        };

        $reviews = $reviewsQuery->paginate(10, ['*'], 'ulasan_page')->withQueryString();

        $siblingBranches = $business->siblingBranches();

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
                        'area' => $business->areaLabel(),
                        'address' => $business->address,
                        'phone' => $business->phone,
                        'status' => $business->status,
                        'is_open' => $business->isOpenNow(),
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
                        'image_url' => $s->imageUrl(),
                        'duration_minutes' => $s->duration_minutes,
                        'price' => $s->price,
                        'daily_quota' => $s->daily_quota,
                        'remaining_quota' => $s->remaining_quota,
                        'is_quota_full' => $s->is_quota_full,
                    ]),
                    'waiting_count' => $waitingCount,
                    'wait_range' => $waitRange,
                ],
            ]);
        }

        return view('customer.show', [
            'business' => $business,
            'services' => $servicesWithQuota,
            'dailyQueue' => $dailyQueue,
            'waitingCount' => $waitingCount,
            'waitRange' => $waitRange,
            'activeTicketCount' => $activeTicketCount,
            'maxActiveTickets' => 3,
            'todayHour' => $todayHour,
            'siblingBranches' => $siblingBranches,
            'reviews' => $reviews,
            'ratingBreakdown' => $ratingBreakdown,
            'reviewsWithCommentCount' => $reviewsWithCommentCount,
            'selectedStar' => $selectedStar,
            'withCommentOnly' => $withCommentOnly,
            'reviewSort' => $reviewSort,
        ]);
    }

    /**
     * Display the dedicated online queue intake form with real-time booking slot options.
     */
    public function create(Request $request, string $slug): View
    {
        /** @var Business $business */
        $business = Business::where('slug', $slug)
            ->with(['category', 'hours'])
            ->withCount('activeCounters as active_counters_count')
            ->withAvg(['services as avg_service_minutes' => fn ($q) => $q->where('is_active', true)], 'duration_minutes')
            ->withMin(['services as min_service_minutes' => fn ($q) => $q->where('is_active', true)], 'duration_minutes')
            ->withMax(['services as max_service_minutes' => fn ($q) => $q->where('is_active', true)], 'duration_minutes')
            ->firstOrFail();

        abort_unless($business->isApproved(), 404);

        $this->queueService->expireStaleCalledTickets($business);

        $dailyQueue = $this->queueService->getOrCreateDailyQueue($business);

        $waitingCount = QueueTicket::where('business_id', $business->id)
            ->where('daily_queue_id', $dailyQueue->id)
            ->where('status', QueueTicket::STATUS_MENUNGGU)
            ->count();

        $services = $business->services()->active()->get()->map(function (Service $service) use ($dailyQueue) {
            $remaining = $service->getRemainingQuotaForDailyQueue($dailyQueue);
            $service->remaining_quota = $remaining;
            $service->is_quota_full = ($service->daily_quota !== null && $remaining !== null && $remaining <= 0);

            return $service;
        });

        $slotService = $this->queueService->slots();
        $bookingSlotsByService = [];
        foreach ($services as $service) {
            $bookingSlotsByService[(string) $service->id] = $slotService
                ->getSlotsForService($business, $service, $dailyQueue)
                ->map(fn (array $slot): array => [
                    'slot_key' => $slot['slot_key'],
                    'label' => $slot['label'],
                    'booked_count' => $slot['booked_count'],
                    'capacity' => $slot['capacity'],
                    'remaining' => $slot['remaining'],
                    'is_started' => $slot['is_started'],
                    'is_full' => $slot['is_full'],
                    'is_available' => $slot['is_available'],
                ])
                ->values()
                ->all();
        }

        $selectedServiceId = (int) $request->query('service_id');
        $idempotencyKey = (string) Str::uuid();
        $user = $request->user();

        return view('customer.form', [
            'business' => $business,
            'services' => $services,
            'bookingSlotsByService' => $bookingSlotsByService,
            'selectedServiceId' => $selectedServiceId,
            'idempotencyKey' => $idempotencyKey,
            'waitingCount' => $waitingCount,
            'waitRange' => $business->customerWaitRange($waitingCount),
            'activeTicketCount' => $user?->activeTicketsCount() ?? 0,
            'maxActiveTickets' => 3,
            'customerUser' => $user,
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
        abort_unless($business->isApproved(), 404);

        // 3. Validate Inputs
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'booking_slot' => ['nullable', 'string', 'max:40'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'notify_email' => ['nullable', 'boolean'],
            'customer_email' => [
                'nullable',
                Rule::requiredIf(fn () => (bool) $request->input('notify_email')),
                'email',
                'max:255',
            ],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ], [
            'customer_email.required' => 'Alamat email wajib diisi jika Anda memilih menerima notifikasi email.',
            'customer_email.email' => 'Format alamat email tidak valid.',
        ]);

        $notifyEmail = ! empty($validated['notify_email']);
        $customerEmail = $notifyEmail && ! empty($validated['customer_email']) ? trim($validated['customer_email']) : null;
        $consentedAt = $customerEmail ? now() : null;

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
                activeSessionTicketIds: is_array($activeSessionTicketIds) ? $activeSessionTicketIds : [],
                customerEmail: $customerEmail,
                customerEmailConsentedAt: $consentedAt,
                customer: $request->user(),
                bookingSlot: $validated['booking_slot'] ?? null
            );

            // Record this ticket ID into customer browser session for session protection
            session()->push('customer_ticket_ids', $ticket->id);

            $queuePosition = $this->queueService->calculateQueuePosition($ticket);
            $waitEstimate = $this->waitingTimeEstimationService->estimateForTicket($ticket, $queuePosition);
            $scheduleEstimate = $this->waitingTimeEstimationService->estimateScheduleForTicket($ticket, $queuePosition);

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
                        'booking_slot' => $ticket->bookingSlotWindowLabel(),
                        'booking_slot_start' => $ticket->bookingSlotLabel(),
                        'access_token' => $ticket->access_token,
                        'tracking_token' => $ticket->access_token,
                        'customer_name' => $ticket->customer_name,
                        'customer_phone_masked' => $maskedPhone,
                        'service_name' => $service->name,
                        'status' => $ticket->status,
                        'queue_position' => $queuePosition,
                        'estimated_wait_minutes' => $waitEstimate['minutes'],
                        'estimated_wait_min_minutes' => $waitEstimate['min_minutes'],
                        'estimated_wait_max_minutes' => $waitEstimate['max_minutes'],
                        'estimated_wait_range' => $waitEstimate['range_label'],
                        'estimated_wait_reason' => $waitEstimate['reason'],
                        'estimated_serve_time' => $scheduleEstimate['estimated_serve_time_label'],
                        'schedule_estimate' => $scheduleEstimate,
                        'tracking_url' => route('customer.queue.track', ['token' => $ticket->access_token]),
                    ],
                ], 201);
            }

            return redirect()->route('customer.queue.track', ['token' => $ticket->access_token])
                ->with('status', 'Tiket antrean Anda berhasil dibuat!')
                ->with('ticket_created', true);
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
     * Strictly scoped to the customer's own ticket (no now_serving leakage of other customers).
     */
    public function track(Request $request, string $token): JsonResponse|Response|View
    {
        // Rate Limiting (M10-08): Protect tracking & polling against denial-of-service (60 requests/minute per IP)
        $throttleKey = 'queue-track-poll:'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 60)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak permintaan pembaruan status antrean. Silakan tunggu sejenak.',
                    'retry_after' => $seconds,
                ], 429, ['Retry-After' => $seconds]);
            }

            return response('Terlalu banyak permintaan pembaruan status antrean. Silakan tunggu sejenak.', 429, ['Retry-After' => $seconds]);
        }
        RateLimiter::hit($throttleKey, 60);

        /** @var QueueTicket $ticket */
        $ticket = QueueTicket::where('access_token', $token)
            ->with(['business.category', 'business.hours', 'service', 'counter', 'review'])
            ->firstOrFail();

        // Sweep stale 15-minute 'dipanggil' tickets and refresh this ticket cleanly
        $this->queueService->expireStaleCalledTickets($ticket->business);
        $ticket->refresh();

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

        // Mask phone number for privacy
        $maskedPhone = null;
        if ($ticket->customer_phone) {
            $phone = $ticket->customer_phone;
            $len = strlen($phone);
            $maskedPhone = $len > 6
                ? substr($phone, 0, 3).'***'.substr($phone, -3)
                : '***';
        }

        $estimate = $this->waitingTimeEstimationService->estimateForTicket($ticket, $position);
        $scheduleEstimate = $this->waitingTimeEstimationService->estimateScheduleForTicket($ticket, $position);

        $data = [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'sequence_number' => $ticket->sequence_number,
            'booking_slot' => $ticket->bookingSlotWindowLabel(),
            'booking_slot_start' => $ticket->bookingSlotLabel(),
            'customer_name' => $ticket->customer_name,
            'customer_phone_masked' => $maskedPhone,
            'customer_phone' => $maskedPhone,
            'status' => $ticket->status,
            'source' => $ticket->source,
            'queue_position' => $position,
            'ahead_count' => $aheadCount,
            'estimated_wait_minutes' => $estimate['minutes'],
            'estimated_wait_min_minutes' => $estimate['min_minutes'],
            'estimated_wait_max_minutes' => $estimate['max_minutes'],
            'estimated_wait_range' => $estimate['range_label'],
            'estimated_wait_reason' => $estimate['reason'],
            'estimated_serve_time' => $scheduleEstimate['estimated_serve_time_label'],
            'has_delay_shift' => $scheduleEstimate['has_delay_shift'],
            'shift_notice' => $scheduleEstimate['shift_notice'],
            'schedule_estimate' => $scheduleEstimate,
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
                'area' => $ticket->business->areaLabel(),
            ],
            'counter' => $ticket->counter ? [
                'name' => $ticket->counter->name,
                'code' => $ticket->counter->code,
            ] : null,
            'counter_name' => $ticket->counter?->name,
            'counter_code' => $ticket->counter?->code,
            'called_at' => $ticket->called_at?->toIso8601String(),
            'service_started_at' => $ticket->service_started_at?->toIso8601String(),
            'completed_at' => $ticket->completed_at?->toIso8601String(),
            'review' => $ticket->review ? [
                'rating' => $ticket->review->rating,
                'comment' => $ticket->review->comment,
                'created_at' => $ticket->review->created_at->toIso8601String(),
            ] : null,
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
            'maskedPhone' => $maskedPhone,
            'estimate' => $estimate,
            'scheduleEstimate' => $scheduleEstimate,
            'isExpired' => false,
            'review' => $ticket->review,
            'qrSvg' => $this->qrCodeService->generateSvg(route('customer.queue.track', ['token' => $ticket->access_token]), 200),
        ]);
    }

    /**
     * Let the customer holding the access token cancel their own waiting ticket.
     */
    public function cancel(Request $request, string $token): JsonResponse|RedirectResponse|Response
    {
        /** @var QueueTicket $ticket */
        $ticket = QueueTicket::where('access_token', $token)
            ->with(['business', 'counter'])
            ->firstOrFail();

        if ($ticket->user_id !== null && $request->user() && $ticket->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki izin untuk membatalkan tiket antrean ini.');
        }

        if ($this->queueService->isTokenExpired($ticket)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'expired',
                    'message' => 'Tiket antrean ini telah kedaluwarsa dan tidak lagi dapat dibatalkan.',
                ], 410);
            }

            return response()->view('customer.expired', [
                'business' => $ticket->business,
                'isExpired' => true,
            ], 410);
        }

        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $ticket = $this->queueService->cancelTicketByCustomer($ticket, $validated['cancellation_reason'] ?? null);
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['queue_error' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Antrean Anda berhasil dibatalkan.',
                'data' => [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'status' => $ticket->status,
                    'cancellation_reason' => $ticket->cancellation_reason,
                ],
            ]);
        }

        return redirect()->route('customer.queue.track', ['token' => $ticket->access_token])
            ->with('status', 'Antrean '.$ticket->ticket_number.' berhasil dibatalkan.');
    }
}

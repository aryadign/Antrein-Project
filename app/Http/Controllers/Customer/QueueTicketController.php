<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Services\QueueService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class QueueTicketController extends Controller
{
    public function __construct(
        protected QueueService $queueService
    ) {}

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
    public function track(Request $request, string $token): JsonResponse|\Illuminate\View\View
    {
        /** @var QueueTicket $ticket */
        $ticket = QueueTicket::where('access_token', $token)
            ->with(['business', 'service'])
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

            return view('preview.customer', [
                'business' => $ticket->business,
                'ticket' => null,
                'isExpired' => true,
            ]);
        }

        $position = $this->queueService->calculateQueuePosition($ticket);
        $servingTicket = $this->queueService->getCurrentServingTicket($ticket->business);

        // Mask phone number for privacy if returned
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
            'estimated_wait_minutes' => $estimatedWaitMinutes,
            'service_name' => $ticket->service->name,
            'business_name' => $ticket->business->name,
            'service' => [
                'name' => $ticket->service->name,
                'duration_minutes' => $ticket->service->duration_minutes,
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

        return view('preview.customer', [
            'business' => $ticket->business,
            'ticket' => $ticket,
            'data' => $data,
            'isExpired' => false,
        ]);
    }
}

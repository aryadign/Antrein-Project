<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCounter;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QueueOperationController extends Controller
{
    public function __construct(
        protected QueueService $queueService
    ) {}

    /**
     * Create a manual walk-in ticket issued by the merchant operator.
     */
    public function walkIn(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'confirmed_paused' => ['nullable', 'boolean'],
        ]);

        /** @var Service $service */
        $service = Service::where('id', $validated['service_id'])
            ->where('business_id', $business->id)
            ->firstOrFail();

        try {
            $ticket = $this->queueService->generateWalkInTicket(
                business: $business,
                service: $service,
                customerName: $validated['customer_name'],
                customerPhone: $validated['customer_phone'] ?? null,
                notes: $validated['notes'] ?? null,
                idempotencyKey: $validated['idempotency_key'] ?? null,
                confirmedPaused: ! empty($validated['confirmed_paused']),
                operator: $user
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tiket walk-in berhasil diterbitkan.',
                    'data' => [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'access_token' => $ticket->access_token,
                        'status' => $ticket->status,
                        'customer_name' => $ticket->customer_name,
                        'service_name' => $service->name,
                    ],
                ], 201);
            }

            return back()->with('status', sprintf('Tiket walk-in %s (%s) berhasil dibuat!', $ticket->ticket_number, $ticket->customer_name));
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['walkin_error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Resolve the operating counter from request, active session, ticket, or user assignments.
     */
    protected function resolveCounter(Request $request, Business $business, User $user, ?QueueTicket $ticket = null): ?BusinessCounter
    {
        $counterId = $request->input('counter_id') ?: session('merchant_active_counter_id');

        if ($counterId) {
            /** @var BusinessCounter|null $counter */
            $counter = BusinessCounter::where('id', $counterId)
                ->where('business_id', $business->id)
                ->first();

            if ($counter && $counter->is_active && $user->canOperateCounter($counter)) {
                return $counter;
            }
        }

        // If operating on an existing ticket that already has an assigned counter, prioritize it if authorized
        if ($ticket && $ticket->counter_id) {
            $ticketCounter = $ticket->counter;
            if ($ticketCounter && $ticketCounter->is_active && $user->canOperateCounter($ticketCounter)) {
                return $ticketCounter;
            }
        }

        return $user->assignedCounters()->first() ?: $business->activeCounters()->first();
    }

    /**
     * Call the next waiting ticket in strict FIFO sequence for a specific counter.
     */
    public function callNext(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        $counter = $this->resolveCounter($request, $business, $user);

        try {
            $ticket = $this->queueService->callNext($business, $user, $counter);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => sprintf('Memanggil antrean %s di %s', $ticket->ticket_number, $ticket->counter?->name ?? 'Loket'),
                    'data' => [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'customer_name' => $ticket->customer_name,
                        'status' => $ticket->status,
                        'counter_id' => $ticket->counter_id,
                        'counter_name' => $ticket->counter?->name,
                        'counter_code' => $ticket->counter?->code,
                        'called_at' => $ticket->called_at?->toIso8601String(),
                    ],
                ]);
            }

            return back()->with('status', sprintf('Memanggil antrean nomor %s (%s) di %s', $ticket->ticket_number, $ticket->customer_name, $ticket->counter?->name ?? 'Loket'));
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['queue_error' => $e->getMessage()]);
        }
    }

    /**
     * Recall a ticket currently in 'dipanggil' status.
     */
    public function recall(Request $request, QueueTicket $ticket): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTicketOwnership($ticket, $user->business);
        $counter = $this->resolveCounter($request, $user->business, $user, $ticket);

        try {
            $this->queueService->recallTicket($ticket, $user, $counter);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => sprintf('Tiket %s berhasil dipanggil ulang.', $ticket->ticket_number),
                    'recalled_at' => $ticket->fresh()->recalled_at?->toIso8601String(),
                ]);
            }

            return back()->with('status', sprintf('Nomor antrean %s berhasil dipanggil ulang.', $ticket->ticket_number));
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['queue_error' => $e->getMessage()]);
        }
    }

    /**
     * Start servicing a called ticket.
     */
    public function startService(Request $request, QueueTicket $ticket): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTicketOwnership($ticket, $user->business);
        $counter = $this->resolveCounter($request, $user->business, $user, $ticket);

        try {
            $this->queueService->startService($ticket, $user, $counter);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => sprintf('Pelayanan untuk tiket %s dimulai.', $ticket->ticket_number),
                    'status' => $ticket->fresh()->status,
                ]);
            }

            return back()->with('status', sprintf('Pelayanan untuk tiket %s (%s) dimulai.', $ticket->ticket_number, $ticket->customer_name));
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['queue_error' => $e->getMessage()]);
        }
    }

    /**
     * Mark ticket service as completed.
     */
    public function complete(Request $request, QueueTicket $ticket): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTicketOwnership($ticket, $user->business);
        $counter = $this->resolveCounter($request, $user->business, $user, $ticket);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->queueService->completeTicket($ticket, $user, $validated['notes'] ?? null, $counter);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => sprintf('Pelayanan tiket %s telah selesai.', $ticket->ticket_number),
                    'status' => $ticket->fresh()->status,
                ]);
            }

            return back()->with('status', sprintf('Pelayanan antrean %s telah selesai.', $ticket->ticket_number));
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['queue_error' => $e->getMessage()]);
        }
    }

    /**
     * Skip an active ticket.
     */
    public function skip(Request $request, QueueTicket $ticket): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTicketOwnership($ticket, $user->business);
        $counter = $this->resolveCounter($request, $user->business, $user, $ticket);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->queueService->skipTicket($ticket, $user, $validated['reason'] ?? null, $counter);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => sprintf('Tiket %s dilewati.', $ticket->ticket_number),
                    'status' => $ticket->fresh()->status,
                ]);
            }

            return back()->with('status', sprintf('Tiket nomor %s dilewati.', $ticket->ticket_number));
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['queue_error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel a waiting ticket with reason.
     */
    public function cancel(Request $request, QueueTicket $ticket): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        // Cancellation of tickets is strictly restricted to Owner (M12 RBAC)
        if ($user->isOperator()) {
            abort(403, 'Akses tidak diizinkan. Pembatalan tiket hanya dapat dilakukan oleh Pemilik Usaha.');
        }

        $this->authorizeTicketOwnership($ticket, $user->business);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->queueService->cancelTicket($ticket, $user, $validated['reason']);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => sprintf('Tiket %s dibatalkan. Kuota layanan telah dilepaskan.', $ticket->ticket_number),
                    'status' => $ticket->fresh()->status,
                ]);
            }

            return back()->with('status', sprintf('Tiket %s berhasil dibatalkan.', $ticket->ticket_number));
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['queue_error' => $e->getMessage()]);
        }
    }

    /**
     * Get queue operational status summary for the merchant console.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;
        $counter = $this->resolveCounter($request, $business, $user);

        $dailyQueue = $this->queueService->getOrCreateDailyQueue($business);

        $nowServing = $this->queueService->getCurrentServingTicket($business, $counter);
        $activeCounters = $this->queueService->getActiveCountersStatus($business);

        $waitingCount = QueueTicket::where('business_id', $business->id)
            ->where('daily_queue_id', $dailyQueue->id)
            ->where('status', QueueTicket::STATUS_MENUNGGU)
            ->count();

        $completedCount = QueueTicket::where('business_id', $business->id)
            ->where('daily_queue_id', $dailyQueue->id)
            ->where('status', QueueTicket::STATUS_SELESAI)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'current_number' => $dailyQueue->current_number,
                'active_counter' => $counter ? [
                    'id' => $counter->id,
                    'name' => $counter->name,
                    'code' => $counter->code,
                ] : null,
                'now_serving' => $nowServing ? [
                    'ticket_number' => $nowServing->ticket_number,
                    'customer_name' => $nowServing->customer_name,
                    'status' => $nowServing->status,
                    'service_name' => $nowServing->service->name,
                    'counter_id' => $nowServing->counter_id,
                    'counter_name' => $nowServing->counter?->name,
                    'called_at' => $nowServing->called_at?->toIso8601String(),
                ] : null,
                'counters_summary' => $activeCounters->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'code' => $c->code,
                    'has_active_ticket' => $c->hasActiveTicket(),
                    'active_ticket_number' => $c->activeTicket?->ticket_number,
                    'active_customer_name' => $c->activeTicket?->customer_name,
                    'active_status' => $c->activeTicket?->status,
                ]),
                'waiting_count' => $waitingCount,
                'completed_count' => $completedCount,
            ],
        ]);
    }

    /**
     * Switch active operational counter for the merchant session.
     */
    public function selectCounter(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        $validated = $request->validate([
            'counter_id' => ['required', 'integer', 'exists:business_counters,id'],
        ]);

        /** @var BusinessCounter $counter */
        $counter = BusinessCounter::where('id', $validated['counter_id'])
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $user->canOperateCounter($counter)) {
            abort(403, 'Anda tidak memiliki hak otorisasi untuk mengoperasikan loket ini.');
        }

        session(['merchant_active_counter_id' => $counter->id]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => sprintf('Loket operasional aktif dialihkan ke %s.', $counter->name),
                'counter' => [
                    'id' => $counter->id,
                    'name' => $counter->name,
                    'code' => $counter->code,
                ],
            ]);
        }

        return back()->with('status', sprintf('Loket aktif berhasil dialihkan ke %s (%s).', $counter->name, $counter->code));
    }

    /**
     * Enforce strict tenant ownership.
     */
    protected function authorizeTicketOwnership(QueueTicket $ticket, Business $business): void
    {
        if ($ticket->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak akses terhadap tiket antrean usaha lain.');
        }
    }
}

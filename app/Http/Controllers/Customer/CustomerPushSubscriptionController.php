<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Models\QueueTicket;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPushSubscriptionController extends Controller
{
    public function __construct(
        protected QueueService $queueService
    ) {}

    /**
     * Store or update a customer browser push subscription for a specific queue ticket.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'endpoint' => ['required', 'string', 'url', 'max:500'],
            'public_key' => ['required', 'string'],
            'auth_token' => ['required', 'string'],
            'content_encoding' => ['nullable', 'string', 'max:20'],
        ]);

        /** @var QueueTicket|null $ticket */
        $ticket = QueueTicket::where('access_token', $validated['token'])->first();

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket antrean tidak ditemukan.',
            ], 404);
        }

        if ($this->queueService->isTokenExpired($ticket)) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket antrean ini telah kedaluwarsa.',
            ], 410);
        }

        $endpoint = $validated['endpoint'];
        $endpointHash = hash('sha256', $endpoint);

        $subscription = PushSubscription::updateOrCreate(
            [
                'endpoint_hash' => $endpointHash,
            ],
            [
                'queue_ticket_id' => $ticket->id,
                'user_id' => null,
                'endpoint' => $endpoint,
                'public_key' => $validated['public_key'],
                'auth_token' => $validated['auth_token'],
                'content_encoding' => $validated['content_encoding'] ?? 'aesgcm',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi browser untuk tiket ini berhasil diaktifkan.',
            'id' => $subscription->id,
        ], 201);
    }

    /**
     * Delete a customer browser push subscription.
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'endpoint' => ['required', 'string', 'url'],
        ]);

        /** @var QueueTicket|null $ticket */
        $ticket = QueueTicket::where('access_token', $validated['token'])->first();

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket antrean tidak ditemukan.',
            ], 404);
        }

        $endpointHash = hash('sha256', $validated['endpoint']);

        $deleted = PushSubscription::where('endpoint_hash', $endpointHash)
            ->where('queue_ticket_id', $ticket->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi browser untuk tiket ini berhasil dinonaktifkan.',
            'deleted' => (bool) $deleted,
        ]);
    }
}


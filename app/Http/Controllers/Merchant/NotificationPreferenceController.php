<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * Update the merchant user's notification preferences.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email_on_new_online_ticket' => ['nullable', 'boolean'],
            'browser_on_new_online_ticket' => ['nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $user->notificationPreference()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'email_on_new_online_ticket' => (bool) ($validated['email_on_new_online_ticket'] ?? false),
                'browser_on_new_online_ticket' => (bool) ($validated['browser_on_new_online_ticket'] ?? false),
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Preferensi notifikasi berhasil diperbarui.',
            ]);
        }

        return redirect()->route('merchant.settings', ['tab' => 'notifications'])
            ->with('status', 'Preferensi notifikasi berhasil diperbarui.');
    }

    /**
     * Store or update a merchant staff browser push subscription.
     */
    public function storeSubscription(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:500'],
            'public_key' => ['required', 'string'],
            'auth_token' => ['required', 'string'],
            'content_encoding' => ['nullable', 'string', 'max:20'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $endpoint = $validated['endpoint'];
        $endpointHash = hash('sha256', $endpoint);

        $subscription = PushSubscription::updateOrCreate(
            [
                'endpoint_hash' => $endpointHash,
            ],
            [
                'user_id' => $user->id,
                'queue_ticket_id' => null,
                'endpoint' => $endpoint,
                'public_key' => $validated['public_key'],
                'auth_token' => $validated['auth_token'],
                'content_encoding' => $validated['content_encoding'] ?? 'aesgcm',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Langganan notifikasi browser merchant berhasil disimpan.',
            'id' => $subscription->id,
        ], 201);
    }

    /**
     * Delete a merchant staff browser push subscription.
     */
    public function destroySubscription(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $endpointHash = hash('sha256', $validated['endpoint']);

        $deleted = PushSubscription::where('endpoint_hash', $endpointHash)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Langganan notifikasi browser merchant berhasil dihapus.',
            'deleted' => (bool) $deleted,
        ]);
    }
}


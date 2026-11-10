<?php

namespace App\Http\Controllers\Broadcasting;

use App\Http\Controllers\Controller;
use App\Models\QueueTicket;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Pusher\Pusher;

class TicketBroadcastAuthController extends Controller
{
    /**
     * Authorize guest customer subscription to private ticket broadcast channel.
     */
    public function authenticate(Request $request): JsonResponse
    {
        // Rate Limiting: 60 attempts per minute per IP
        $throttleKey = 'ticket-broadcast-auth:'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 60)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => 'Terlalu banyak permintaan otorisasi saluran.',
                'retry_after' => $seconds,
            ], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        $validated = $request->validate([
            'socket_id' => ['required', 'string'],
            'channel_name' => ['required', 'string'],
            'token' => ['required', 'string', 'min:10', 'max:64'],
        ]);

        $channelName = $validated['channel_name'];
        $socketId = $validated['socket_id'];
        $token = $validated['token'];

        // Channel name must match pattern private-ticket.{tokenHash}
        $expectedPrefix = 'private-ticket.';
        if (! str_starts_with($channelName, $expectedPrefix)) {
            return response()->json(['message' => 'Saluran tidak valid.'], 403);
        }

        $tokenHash = substr($channelName, strlen($expectedPrefix));
        if (hash('sha256', $token) !== $tokenHash) {
            return response()->json(['message' => 'Token akses tidak cocok dengan saluran.'], 403);
        }

        /** @var QueueTicket|null $ticket */
        $ticket = QueueTicket::where('access_token', $token)->first();
        if (! $ticket) {
            return response()->json(['message' => 'Tiket tidak ditemukan.'], 404);
        }

        // Check if ticket is older than 48 hours
        if ($ticket->created_at && $ticket->created_at->lt(Carbon::now()->subHours(48))) {
            return response()->json(['message' => 'Tiket telah kedaluwarsa.'], 410);
        }

        // Generate Pusher/Reverb auth signature
        $key = config('broadcasting.connections.reverb.key')
            ?? config('broadcasting.connections.pusher.key')
            ?? env('REVERB_APP_KEY', 'antrein-key');

        $secret = config('broadcasting.connections.reverb.secret')
            ?? config('broadcasting.connections.pusher.secret')
            ?? env('REVERB_APP_SECRET', 'antrein-secret');

        $appId = config('broadcasting.connections.reverb.app_id')
            ?? config('broadcasting.connections.pusher.app_id')
            ?? env('REVERB_APP_ID', 'antrein-app');

        try {
            if (class_exists(Pusher::class) && ! empty($key) && ! empty($secret)) {
                $options = config('broadcasting.connections.reverb.options', []);
                $pusher = new Pusher($key, $secret, $appId, $options);
                $authJson = $pusher->authorizeChannel($channelName, $socketId);

                return response()->json(json_decode($authJson, true));
            }
        } catch (\Throwable $e) {
            // Fallback manual HMAC signature
        }

        $stringToSign = $socketId.':'.$channelName;
        $signature = hash_hmac('sha256', $stringToSign, (string) $secret);

        return response()->json([
            'auth' => $key.':'.$signature,
        ]);
    }
}

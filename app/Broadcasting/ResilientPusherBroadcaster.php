<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Broadcasting\BroadcastException;

/**
 * Pusher/Reverb broadcaster that never lets a real-time delivery failure break the request.
 *
 * Queue actions (take, call, cancel, finish) are committed to the database before their
 * events broadcast. If the WebSocket server is down, the action has still succeeded, so the
 * failure is reported to the log and clients fall back to their polling refresh.
 */
class ResilientPusherBroadcaster extends PusherBroadcaster
{
    /**
     * Broadcast the given event, reporting instead of throwing when the server is unreachable.
     *
     * @param  array<int, mixed>  $channels
     * @param  string  $event
     * @param  array<string, mixed>  $payload
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        try {
            parent::broadcast($channels, $event, $payload);
        } catch (BroadcastException $exception) {
            report($exception);
        }
    }
}

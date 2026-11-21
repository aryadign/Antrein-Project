<?php

namespace Tests\Feature\Broadcasting;

use App\Broadcasting\ResilientPusherBroadcaster;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class ResilientBroadcastTest extends TestCase
{
    public function test_reverb_connection_uses_the_resilient_broadcaster(): void
    {
        $this->assertInstanceOf(ResilientPusherBroadcaster::class, Broadcast::connection('reverb'));
    }

    public function test_unreachable_websocket_server_does_not_throw(): void
    {
        config([
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            'broadcasting.connections.reverb.options.port' => 1,
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);
        Broadcast::purge('reverb');

        Broadcast::connection('reverb')->broadcast(['queue.test'], 'TestEvent', ['ok' => true]);

        $this->assertTrue(true);
    }
}

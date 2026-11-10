<?php

namespace Tests\Feature\Broadcasting;

use App\Events\CounterStatusChanged;
use App\Events\QueueTicketCounterAssigned;
use App\Events\QueueTicketCreated;
use App\Events\QueueTicketStatusChanged;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\BusinessMembership;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeQueueUpdatesTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected Service $service;
    protected DailyQueue $dailyQueue;
    protected BusinessCounter $counter;
    protected QueueService $queueService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueService = app(QueueService::class);

        $category = BusinessCategory::create([
            'name' => 'Klinik & Kesehatan',
            'slug' => 'klinik-kesehatan',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->merchant()->create();
        $this->business = Business::create([
            'user_id' => $this->owner->id,
            'category_id' => $category->id,
            'name' => 'Klinik Pratama AntreIn',
            'slug' => 'klinik-pratama-antrein',
            'status' => Business::STATUS_BUKA,
            'timezone' => 'Asia/Makassar',
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Pemeriksaan Dokter Umum',
            'duration_minutes' => 15,
            'is_active' => true,
        ]);

        $this->dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => now()->setTimezone('Asia/Makassar')->toDateString(),
            'current_number' => 0,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        $this->counter = BusinessCounter::create([
            'business_id' => $this->business->id,
            'name' => 'Loket 1',
            'code' => 'L1',
            'is_active' => true,
        ]);
    }

    public function test_broadcasting_and_reverb_configurations_are_defined(): void
    {
        $this->assertArrayHasKey('reverb', config('broadcasting.connections'));
        $this->assertSame('reverb', config('broadcasting.connections.reverb.driver'));
        $this->assertNotNull(config('reverb.servers.reverb'));
    }

    public function test_event_classes_implement_broadcast_now_and_dispatch_after_commit(): void
    {
        $eventClasses = [
            QueueTicketCreated::class,
            QueueTicketStatusChanged::class,
            QueueTicketCounterAssigned::class,
            CounterStatusChanged::class,
        ];

        foreach ($eventClasses as $class) {
            $interfaces = class_implements($class);
            $this->assertArrayHasKey(
                ShouldBroadcastNow::class,
                $interfaces,
                "Class {$class} must implement ShouldBroadcastNow"
            );
            $this->assertArrayHasKey(
                ShouldDispatchAfterCommit::class,
                $interfaces,
                "Class {$class} must implement ShouldDispatchAfterCommit"
            );
        }
    }

    public function test_queue_ticket_created_event_channels_and_sanitized_payload(): void
    {
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => str_repeat('a', 40),
        ]);

        $event = new QueueTicketCreated($ticket);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-business.'.$this->business->id, $channels[0]->name);
        $this->assertSame('QueueTicketCreated', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertSame($ticket->id, $payload['ticket_id']);
        $this->assertSame('A-001', $payload['ticket_number']);
        $this->assertSame('Budi Santoso', $payload['customer_name']);
        $this->assertSame(QueueTicket::STATUS_MENUNGGU, $payload['status']);

        // Privacy assertion: Phone and access token must NEVER be exposed in broadcast payload
        $this->assertArrayNotHasKey('customer_phone', $payload);
        $this->assertArrayNotHasKey('access_token', $payload);
    }

    public function test_queue_ticket_status_changed_event_channels_and_payload(): void
    {
        $accessToken = str_repeat('b', 40);
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'counter_id' => $this->counter->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Siti Rahma',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'access_token' => $accessToken,
            'called_at' => now(),
        ]);

        $event = new QueueTicketStatusChanged($ticket, QueueTicket::STATUS_MENUNGGU);
        $channels = $event->broadcastOn();

        $this->assertCount(2, $channels);
        $this->assertSame('private-business.'.$this->business->id, $channels[0]->name);
        $this->assertSame('private-ticket.'.hash('sha256', $accessToken), $channels[1]->name);

        $payload = $event->broadcastWith();
        $this->assertSame('A-002', $payload['ticket_number']);
        $this->assertSame(QueueTicket::STATUS_DIPANGGIL, $payload['status']);
        $this->assertSame(QueueTicket::STATUS_MENUNGGU, $payload['previous_status']);
        $this->assertSame('Loket 1', $payload['counter_name']);
        $this->assertArrayNotHasKey('access_token', $payload);
    }

    public function test_queue_ticket_counter_assigned_event_channels_and_payload(): void
    {
        $accessToken = str_repeat('c', 40);
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'counter_id' => $this->counter->id,
            'ticket_number' => 'A-003',
            'sequence_number' => 3,
            'customer_name' => 'Ahmad Dahlan',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'access_token' => $accessToken,
        ]);

        $event = new QueueTicketCounterAssigned($ticket, $this->counter);
        $channels = $event->broadcastOn();

        $this->assertCount(3, $channels);
        $this->assertSame('private-business.'.$this->business->id, $channels[0]->name);
        $this->assertSame('private-counter.'.$this->counter->id, $channels[1]->name);
        $this->assertSame('private-ticket.'.hash('sha256', $accessToken), $channels[2]->name);

        $payload = $event->broadcastWith();
        $this->assertSame($this->counter->id, $payload['counter_id']);
        $this->assertSame('Loket 1', $payload['counter_name']);
        $this->assertSame('L1', $payload['counter_code']);
    }

    public function test_counter_status_changed_event_channels_and_payload(): void
    {
        $event = new CounterStatusChanged($this->counter);
        $channels = $event->broadcastOn();

        $this->assertCount(2, $channels);
        $this->assertSame('private-business.'.$this->business->id, $channels[0]->name);
        $this->assertSame('private-counter.'.$this->counter->id, $channels[1]->name);

        $payload = $event->broadcastWith();
        $this->assertSame($this->counter->id, $payload['counter_id']);
        $this->assertSame('Loket 1', $payload['counter_name']);
        $this->assertTrue($payload['is_active']);
    }

    public function test_ticket_broadcast_auth_endpoint_authenticates_valid_customer_token(): void
    {
        $accessToken = str_repeat('d', 40);
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-004',
            'sequence_number' => 4,
            'customer_name' => 'Dewi Lestari',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => $accessToken,
        ]);

        $tokenHash = hash('sha256', $accessToken);

        $response = $this->postJson(route('broadcasting.auth.ticket'), [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-ticket.'.$tokenHash,
            'token' => $accessToken,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['auth']);
    }

    public function test_ticket_broadcast_auth_endpoint_rejects_mismatched_token_hash(): void
    {
        $accessToken = str_repeat('e', 40);
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-005',
            'sequence_number' => 5,
            'customer_name' => 'Eko Prasetyo',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => $accessToken,
        ]);

        $response = $this->postJson(route('broadcasting.auth.ticket'), [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-ticket.invalidhash123',
            'token' => $accessToken,
        ]);

        $response->assertStatus(403);
    }

    public function test_ticket_broadcast_auth_endpoint_rejects_nonexistent_ticket(): void
    {
        $nonExistentToken = str_repeat('f', 40);
        $response = $this->postJson(route('broadcasting.auth.ticket'), [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-ticket.'.hash('sha256', $nonExistentToken),
            'token' => $nonExistentToken,
        ]);

        $response->assertStatus(404);
    }

    public function test_online_and_walk_in_ticket_generation_dispatches_queue_ticket_created(): void
    {
        Event::fake([QueueTicketCreated::class]);

        $onlineTicket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Customer Online 1',
            customerPhone: '0812345678'
        );

        Event::assertDispatched(QueueTicketCreated::class, function ($event) use ($onlineTicket) {
            return $event->ticket->id === $onlineTicket->id
                && $event->ticket->source === QueueTicket::SOURCE_ONLINE;
        });

        $walkInTicket = $this->queueService->generateWalkInTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Customer Walk-in 1',
            customerPhone: '0812345679',
            operator: $this->owner
        );

        Event::assertDispatched(QueueTicketCreated::class, function ($event) use ($walkInTicket) {
            return $event->ticket->id === $walkInTicket->id
                && $event->ticket->source === QueueTicket::SOURCE_WALK_IN;
        });
    }

    public function test_call_next_dispatches_assigned_status_changed_and_counter_status_changed(): void
    {
        Event::fake([
            QueueTicketCounterAssigned::class,
            QueueTicketStatusChanged::class,
            CounterStatusChanged::class,
        ]);

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-010',
            'sequence_number' => 10,
            'customer_name' => 'Test Call Next',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => str_repeat('g', 40),
        ]);

        $calledTicket = $this->queueService->callNext($this->business, $this->owner, $this->counter);

        $this->assertSame($ticket->id, $calledTicket->id);
        $this->assertSame(QueueTicket::STATUS_DIPANGGIL, $calledTicket->status);

        Event::assertDispatched(QueueTicketCounterAssigned::class, function ($event) use ($ticket) {
            return $event->ticket->id === $ticket->id
                && $event->counter->id === $this->counter->id;
        });

        Event::assertDispatched(QueueTicketStatusChanged::class, function ($event) use ($ticket) {
            return $event->ticket->id === $ticket->id
                && $event->ticket->status === QueueTicket::STATUS_DIPANGGIL;
        });

        Event::assertDispatched(CounterStatusChanged::class, function ($event) {
            return $event->counter->id === $this->counter->id;
        });
    }

    public function test_recall_start_service_complete_skip_and_cancel_dispatch_events(): void
    {
        Event::fake([
            QueueTicketStatusChanged::class,
            CounterStatusChanged::class,
        ]);

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'counter_id' => $this->counter->id,
            'ticket_number' => 'A-011',
            'sequence_number' => 11,
            'customer_name' => 'Test Lifecycle',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'access_token' => str_repeat('h', 40),
            'called_at' => now(),
        ]);

        // Recall
        $this->queueService->recallTicket($ticket, $this->owner, $this->counter);
        Event::assertDispatched(QueueTicketStatusChanged::class);

        // Start service
        $this->queueService->startService($ticket, $this->owner, $this->counter);
        $this->assertSame(QueueTicket::STATUS_SEDANG_DILAYANI, $ticket->fresh()->status);

        // Complete service
        $this->queueService->completeTicket($ticket, $this->owner, 'Selesai cepat', $this->counter);
        $this->assertSame(QueueTicket::STATUS_SELESAI, $ticket->fresh()->status);

        // Test Skip
        $ticketToSkip = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'counter_id' => $this->counter->id,
            'ticket_number' => 'A-012',
            'sequence_number' => 12,
            'customer_name' => 'Test Skip',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => str_repeat('i', 40),
        ]);
        $this->queueService->skipTicket($ticketToSkip, $this->owner, 'Pelanggan tidak hadir', $this->counter);
        $this->assertSame(QueueTicket::STATUS_DILEWATI, $ticketToSkip->fresh()->status);

        // Test Cancel
        $ticketToCancel = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-013',
            'sequence_number' => 13,
            'customer_name' => 'Test Cancel',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => str_repeat('j', 40),
        ]);
        $this->queueService->cancelTicket($ticketToCancel, $this->owner, 'Dibatalkan oleh pelanggan');
        $this->assertSame(QueueTicket::STATUS_DIBATALKAN, $ticketToCancel->fresh()->status);
    }

    public function test_counter_toggle_status_dispatches_counter_status_changed(): void
    {
        Event::fake([CounterStatusChanged::class]);

        // Create a 2nd counter so deactivating one is permitted by guard
        $counter2 = BusinessCounter::create([
            'business_id' => $this->business->id,
            'name' => 'Loket 2',
            'code' => 'L2',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->patch(route('merchant.counters.toggle-status', ['counter' => $this->counter->id]));

        $response->assertSessionHas('status');

        Event::assertDispatched(CounterStatusChanged::class, function ($event) {
            return $event->counter->id === $this->counter->id;
        });
    }
}

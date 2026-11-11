<?php

namespace Tests\Feature\QA;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedFifoQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;
    private Service $service;
    private QueueService $queueService;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Klinik & Perawatan',
            'slug' => 'klinik-perawatan',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik AntreIn Prima',
            'slug' => 'klinik-antrein-prima',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Pemeriksaan Umum',
            'duration_minutes' => 15,
            'is_active' => true,
        ]);

        $this->queueService = app(QueueService::class);
    }

    public function test_unified_fifo_interleaves_online_and_walkin_tickets(): void
    {
        // 1. Online Ticket
        $ticket1 = $this->queueService->generateOnlineTicket(
            $this->business,
            $this->service,
            'Pelanggan Online 1',
            '081111111111'
        );

        // 2. Walk-in Ticket
        $ticket2 = $this->queueService->generateWalkInTicket(
            $this->business,
            $this->service,
            'Pelanggan Walk-in 2',
            '082222222222',
            operator: $this->merchant
        );

        // 3. Online Ticket
        $ticket3 = $this->queueService->generateOnlineTicket(
            $this->business,
            $this->service,
            'Pelanggan Online 3',
            '083333333333'
        );

        // 4. Walk-in Ticket
        $ticket4 = $this->queueService->generateWalkInTicket(
            $this->business,
            $this->service,
            'Pelanggan Walk-in 4',
            '084444444444',
            operator: $this->merchant
        );

        // Verify sequence order
        $this->assertEquals(1, $ticket1->sequence_number);
        $this->assertEquals(2, $ticket2->sequence_number);
        $this->assertEquals(3, $ticket3->sequence_number);
        $this->assertEquals(4, $ticket4->sequence_number);

        // Call Next: Must return ticket 1
        $called1 = $this->queueService->callNext($this->business, $this->merchant);
        $this->assertEquals($ticket1->id, $called1->id);
        $this->assertEquals(QueueTicket::STATUS_DIPANGGIL, $called1->status);

        // Start Serving and Complete ticket 1
        $this->queueService->startService($called1, $this->merchant);
        $this->queueService->completeTicket($called1->fresh(), $this->merchant);

        // Call Next: Must return ticket 2
        $called2 = $this->queueService->callNext($this->business, $this->merchant);
        $this->assertEquals($ticket2->id, $called2->id);

        $this->queueService->startService($called2, $this->merchant);
        $this->queueService->completeTicket($called2->fresh(), $this->merchant);

        // Call Next: Must return ticket 3
        $called3 = $this->queueService->callNext($this->business, $this->merchant);
        $this->assertEquals($ticket3->id, $called3->id);
        $this->queueService->startService($called3, $this->merchant);
        $this->queueService->completeTicket($called3->fresh(), $this->merchant);

        // Call Next: Must return ticket 4
        $called4 = $this->queueService->callNext($this->business, $this->merchant);
        $this->assertEquals($ticket4->id, $called4->id);
        $this->queueService->startService($called4, $this->merchant);
        $this->queueService->completeTicket($called4->fresh(), $this->merchant);
    }

    public function test_single_active_operator_rule_enforced(): void
    {
        $this->queueService->generateOnlineTicket($this->business, $this->service, 'Pelanggan 1');
        $this->queueService->generateOnlineTicket($this->business, $this->service, 'Pelanggan 2');

        // Call first ticket
        $this->queueService->callNext($this->business, $this->merchant);

        // Attempting to call next while ticket 1 is DIPANGGIL must fail
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Tidak dapat memanggil antrean berikutnya');

        $this->queueService->callNext($this->business, $this->merchant);
    }

    public function test_status_transitions_and_history_logging(): void
    {
        $ticket = $this->queueService->generateOnlineTicket($this->business, $this->service, 'Pelanggan Lifecycle');

        $this->assertEquals(QueueTicket::STATUS_MENUNGGU, $ticket->status);

        // Call
        $calledTicket = $this->queueService->callNext($this->business, $this->merchant);
        $this->assertEquals(QueueTicket::STATUS_DIPANGGIL, $calledTicket->status);
        $this->assertNotNull($calledTicket->called_at);

        // Start Serving
        $servingTicket = $this->queueService->startService($calledTicket, $this->merchant);
        $this->assertEquals(QueueTicket::STATUS_SEDANG_DILAYANI, $servingTicket->status);
        $this->assertNotNull($servingTicket->service_started_at);

        // Complete
        $completedTicket = $this->queueService->completeTicket($servingTicket, $this->merchant);
        $this->assertEquals(QueueTicket::STATUS_SELESAI, $completedTicket->status);
        $this->assertNotNull($completedTicket->completed_at);

        // Check history records
        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $ticket->id,
            'to_status' => QueueTicket::STATUS_DIPANGGIL,
        ]);
        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $ticket->id,
            'to_status' => QueueTicket::STATUS_SEDANG_DILAYANI,
        ]);
        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $ticket->id,
            'to_status' => QueueTicket::STATUS_SELESAI,
        ]);
    }
}

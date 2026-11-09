<?php

namespace Tests\Feature\Queue;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueStatusHistory;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private QueueService $queueService;
    private User $merchant;
    private Business $business;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueService = app(QueueService::class);

        $category = BusinessCategory::create([
            'name' => 'Klinik Estetika',
            'slug' => 'klinik-estetika',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Glow Beauty Clinic',
            'slug' => 'glow-beauty-clinic',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Facial Treatment',
            'duration_minutes' => 45,
            'daily_quota' => 30,
            'is_active' => true,
        ]);
    }

    public function test_fifo_ordering_and_call_next(): void
    {
        $t1 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Customer 1',
            customerPhone: '081234567001'
        );

        $t2 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Customer 2',
            customerPhone: '081234567002'
        );

        $response = $this->actingAs($this->merchant)->postJson(route('merchant.queues.call-next'));
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'ticket_id' => $t1->id,
                'ticket_number' => 'A-001',
                'status' => QueueTicket::STATUS_CALLED,
            ],
        ]);

        $t1->refresh();
        $this->assertEquals(QueueTicket::STATUS_CALLED, $t1->status);
        $this->assertNotNull($t1->called_at);

        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $t1->id,
            'from_status' => QueueTicket::STATUS_WAITING,
            'to_status' => QueueTicket::STATUS_CALLED,
            'actor_type' => 'merchant',
            'actor_id' => $this->merchant->id,
        ]);
    }

    public function test_single_active_operator_rule_prevents_calling_next_while_ticket_is_active(): void
    {
        $t1 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Customer 1',
            customerPhone: '081234567001'
        );

        $t2 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Customer 2',
            customerPhone: '081234567002'
        );

        // Call t1
        $this->actingAs($this->merchant)->postJson(route('merchant.queues.call-next'))->assertOk();

        // Calling next while t1 is dipanggil should fail
        $response = $this->actingAs($this->merchant)->postJson(route('merchant.queues.call-next'));
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('sedang dipanggil atau sedang dilayani', $response->json('message'));

        // Start service for t1
        $this->actingAs($this->merchant)->postJson(route('merchant.queues.start-service', ['ticket' => $t1->id]))->assertOk();

        // Calling next while t1 is sedang_dilayani should also fail
        $response2 = $this->actingAs($this->merchant)->postJson(route('merchant.queues.call-next'));
        $response2->assertStatus(422);
        $response2->assertJson([
            'success' => false,
        ]);
    }

    public function test_recall_ticket_records_recall_event(): void
    {
        $t1 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Customer 1',
            customerPhone: '081234567001'
        );

        $this->queueService->callNext($this->business, $this->merchant);

        $response = $this->actingAs($this->merchant)->postJson(route('merchant.queues.recall', ['ticket' => $t1->id]));
        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertStringContainsString('dipanggil ulang', $response->json('message'));

        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $t1->id,
            'event_type' => QueueStatusHistory::EVENT_TICKET_RECALLED,
            'from_status' => QueueTicket::STATUS_CALLED,
            'to_status' => QueueTicket::STATUS_CALLED,
            'actor_type' => 'merchant',
            'actor_id' => $this->merchant->id,
        ]);
    }

    public function test_complete_ticket_workflow(): void
    {
        $t1 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Customer 1',
            customerPhone: '081234567001'
        );

        $this->queueService->callNext($this->business, $this->merchant);
        $t1->refresh();
        $this->queueService->startService($t1, $this->merchant);
        $t1->refresh();

        $response = $this->actingAs($this->merchant)->postJson(route('merchant.queues.complete', ['ticket' => $t1->id]));
        $response->assertOk();

        $t1->refresh();
        $this->assertEquals(QueueTicket::STATUS_COMPLETED, $t1->status);
        $this->assertNotNull($t1->completed_at);

        // Verify status history
        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $t1->id,
            'from_status' => QueueTicket::STATUS_SERVING,
            'to_status' => QueueTicket::STATUS_COMPLETED,
            'actor_type' => 'merchant',
            'actor_id' => $this->merchant->id,
        ]);

        // Terminal status: cannot recall, cannot start, cannot complete again
        $this->actingAs($this->merchant)->postJson(route('merchant.queues.recall', ['ticket' => $t1->id]))->assertStatus(422);
        $this->actingAs($this->merchant)->postJson(route('merchant.queues.start-service', ['ticket' => $t1->id]))->assertStatus(422);
        $this->actingAs($this->merchant)->postJson(route('merchant.queues.complete', ['ticket' => $t1->id]))->assertStatus(422);
    }

    public function test_skip_ticket_workflow(): void
    {
        $t1 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Customer 1',
            customerPhone: '081234567001'
        );

        $this->queueService->callNext($this->business, $this->merchant);
        $t1->refresh();

        $response = $this->actingAs($this->merchant)->postJson(route('merchant.queues.skip', ['ticket' => $t1->id]));
        $response->assertOk();

        $t1->refresh();
        $this->assertEquals(QueueTicket::STATUS_SKIPPED, $t1->status);
        $this->assertNotNull($t1->skipped_at);

        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $t1->id,
            'event_type' => QueueStatusHistory::EVENT_TICKET_SKIPPED,
            'from_status' => QueueTicket::STATUS_CALLED,
            'to_status' => QueueTicket::STATUS_SKIPPED,
            'actor_type' => 'merchant',
            'actor_id' => $this->merchant->id,
        ]);
    }

    public function test_cancel_ticket_requires_reason_and_must_be_in_waiting_status(): void
    {
        $t1 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Customer 1',
            customerPhone: '081234567001'
        );

        // Attempt cancel without reason
        $resWithoutReason = $this->actingAs($this->merchant)->postJson(route('merchant.queues.cancel', ['ticket' => $t1->id]), []);
        $resWithoutReason->assertStatus(422);

        // Cancel with reason
        $resWithReason = $this->actingAs($this->merchant)->postJson(route('merchant.queues.cancel', ['ticket' => $t1->id]), [
            'reason' => 'Pelanggan membatalkan kedatangan via telepon',
        ]);
        $resWithReason->assertOk();

        $t1->refresh();
        $this->assertEquals(QueueTicket::STATUS_CANCELLED, $t1->status);
        $this->assertStringContainsString('Pelanggan membatalkan', $t1->cancellation_reason);

        // Cannot cancel already cancelled ticket
        $this->actingAs($this->merchant)->postJson(route('merchant.queues.cancel', ['ticket' => $t1->id]), [
            'reason' => 'Batal lagi',
        ])->assertStatus(422);
    }

    public function test_merchant_cannot_manipulate_another_merchants_tickets(): void
    {
        $otherMerchant = User::factory()->merchant()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherMerchant->id,
            'category_id' => $this->business->category_id,
            'name' => 'Other Clinic',
            'slug' => 'other-clinic',
            'phone' => '081234567999',
            'status' => Business::STATUS_BUKA,
        ]);
        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Service',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $ticket = $this->queueService->generateOnlineTicket(
            business: $otherBusiness,
            service: $otherService,
            customerName: 'Victim Customer',
            customerPhone: '081234567999'
        );

        // Authenticated as $this->merchant, attempt to recall or complete $otherBusiness's ticket
        $res = $this->actingAs($this->merchant)->postJson(route('merchant.queues.recall', ['ticket' => $ticket->id]));
        $res->assertForbidden();
    }
}

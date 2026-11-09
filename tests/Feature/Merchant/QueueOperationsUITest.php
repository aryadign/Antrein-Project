<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueOperationsUITest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;
    private Service $service;
    private DailyQueue $dailyQueue;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Bengkel Motor',
            'slug' => 'bengkel-motor',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Bengkel Motor Jaya',
            'slug' => 'bengkel-motor-jaya',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Servis Ringan & Ganti Oli',
            'duration_minutes' => 30,
            'price' => 85000,
            'daily_quota' => 20,
            'is_active' => true,
        ]);

        $this->dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::now('Asia/Makassar')->toDateString(),
            'current_number' => 0,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);
    }

    public function test_merchant_can_issue_walk_in_ticket(): void
    {
        $response = $this->actingAs($this->merchant)->post(route('merchant.queues.walk-in'), [
            'service_id' => $this->service->id,
            'customer_name' => 'Komang Arimbawa',
            'customer_phone' => '081234567899',
            'notes' => 'Ganti oli MPX1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('queue_tickets', [
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'customer_name' => 'Komang Arimbawa',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_MENUNGGU,
        ]);
    }

    public function test_merchant_walk_in_in_jeda_status_requires_confirmed_paused(): void
    {
        $this->business->update(['status' => Business::STATUS_JEDA]);

        // Without confirmed_paused -> rejected
        $responseFail = $this->actingAs($this->merchant)->post(route('merchant.queues.walk-in'), [
            'service_id' => $this->service->id,
            'customer_name' => 'Pelanggan Jeda',
        ]);

        $responseFail->assertSessionHasErrors('walkin_error');

        // With confirmed_paused -> succeeds
        $responseSuccess = $this->actingAs($this->merchant)->post(route('merchant.queues.walk-in'), [
            'service_id' => $this->service->id,
            'customer_name' => 'Pelanggan Jeda Dikonfirmasi',
            'confirmed_paused' => 1,
        ]);

        $responseSuccess->assertSessionHas('status');
        $this->assertDatabaseHas('queue_tickets', [
            'customer_name' => 'Pelanggan Jeda Dikonfirmasi',
        ]);
    }

    public function test_merchant_can_call_next_waiting_ticket_in_strict_fifo_sequence(): void
    {
        // First ticket
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Pertama',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-fifo-1',
        ]);

        // Second ticket
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Pelanggan Kedua',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-fifo-2',
        ]);

        $response = $this->actingAs($this->merchant)->post(route('merchant.queues.call-next'));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('queue_tickets', [
            'ticket_number' => 'A-001',
            'status' => QueueTicket::STATUS_DIPANGGIL,
        ]);

        $this->assertDatabaseHas('queue_tickets', [
            'ticket_number' => 'A-002',
            'status' => QueueTicket::STATUS_MENUNGGU,
        ]);
    }

    public function test_merchant_cannot_call_next_if_an_active_serving_ticket_already_exists(): void
    {
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Sedang Dipanggil',
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-serving-1',
        ]);

        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Pelanggan Menunggu',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-serving-2',
        ]);

        $response = $this->actingAs($this->merchant)->post(route('merchant.queues.call-next'));

        $response->assertSessionHasErrors('queue_error');
    }

    public function test_merchant_can_recall_start_complete_and_skip_ticket(): void
    {
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Uji',
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-lifecycle',
        ]);

        // 1. Recall
        $resRecall = $this->actingAs($this->merchant)->post(route('merchant.queues.recall', $ticket));
        $resRecall->assertSessionHas('status');
        $this->assertNotNull($ticket->fresh()->recalled_at);

        // 2. Start Service
        $resStart = $this->actingAs($this->merchant)->post(route('merchant.queues.start-service', $ticket));
        $resStart->assertSessionHas('status');
        $this->assertEquals(QueueTicket::STATUS_SEDANG_DILAYANI, $ticket->fresh()->status);

        // 3. Complete Ticket
        $resComplete = $this->actingAs($this->merchant)->post(route('merchant.queues.complete', $ticket));
        $resComplete->assertSessionHas('status');
        $this->assertEquals(QueueTicket::STATUS_SELESAI, $ticket->fresh()->status);
    }

    public function test_merchant_can_skip_ticket_in_dipanggil_status(): void
    {
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-005',
            'sequence_number' => 5,
            'customer_name' => 'Pelanggan Lewat',
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-skip-5',
        ]);

        $response = $this->actingAs($this->merchant)->post(route('merchant.queues.skip', $ticket));

        $response->assertSessionHas('status');
        $this->assertEquals(QueueTicket::STATUS_DILEWATI, $ticket->fresh()->status);
    }

    public function test_merchant_can_cancel_ticket_with_reason(): void
    {
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-008',
            'sequence_number' => 8,
            'customer_name' => 'Pelanggan Batal',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-cancel-8',
        ]);

        $response = $this->actingAs($this->merchant)->post(route('merchant.queues.cancel', $ticket), [
            'reason' => 'Pelanggan membatalkan janji karena hujan lebat',
        ]);

        $response->assertSessionHas('status');
        $this->assertEquals(QueueTicket::STATUS_DIBATALKAN, $ticket->fresh()->status);
        $this->assertEquals('Pelanggan membatalkan janji karena hujan lebat', $ticket->fresh()->cancellation_reason);
    }

    public function test_merchant_cannot_operate_on_ticket_belonging_to_another_business(): void
    {
        $otherMerchant = User::factory()->merchant()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherMerchant->id,
            'name' => 'Bengkel Sebelah',
            'slug' => 'bengkel-sebelah',
            'phone' => '086666666666',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);
        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Layanan Tetangga',
            'duration_minutes' => 30,
            'price' => 50000,
            'is_active' => true,
        ]);
        $otherDailyQueue = DailyQueue::create([
            'business_id' => $otherBusiness->id,
            'queue_date' => Carbon::now('Asia/Makassar')->toDateString(),
            'current_number' => 1,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);
        $alienTicket = QueueTicket::create([
            'business_id' => $otherBusiness->id,
            'daily_queue_id' => $otherDailyQueue->id,
            'service_id' => $otherService->id,
            'ticket_number' => 'ALIEN-99',
            'sequence_number' => 1,
            'customer_name' => 'Alien Customer',
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-alien-op',
        ]);

        // Attempt recall on alien ticket
        $resRecall = $this->actingAs($this->merchant)->post(route('merchant.queues.recall', $alienTicket));
        $resRecall->assertForbidden();

        // Attempt cancel on alien ticket
        $resCancel = $this->actingAs($this->merchant)->post(route('merchant.queues.cancel', $alienTicket), [
            'reason' => 'Hacking attempt',
        ]);
        $resCancel->assertForbidden();
    }
}

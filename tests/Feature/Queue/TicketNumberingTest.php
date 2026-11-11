<?php

namespace Tests\Feature\Queue;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketNumberingTest extends TestCase
{
    use RefreshDatabase;

    private QueueService $queueService;
    private Business $business;
    private Service $service;
    private User $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueService = app(QueueService::class);

        $category = BusinessCategory::create([
            'name' => 'Klinik & Salon',
            'slug' => 'klinik-salon',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Denpasar Sehat',
            'slug' => 'klinik-denpasar-sehat',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Konsultasi Dokter Umum',
            'duration_minutes' => 20,
            'daily_quota' => 50,
            'is_active' => true,
        ]);
    }

    public function test_ticket_numbering_starts_at_a001_and_increments_sequentially(): void
    {
        $ticket1 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Budi Santoso',
            customerPhone: '081234567801'
        );

        $this->assertEquals('A-001', $ticket1->ticket_number);
        $this->assertEquals(1, $ticket1->sequence_number);

        $ticket2 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Siti Nurhaliza',
            customerPhone: '081234567802'
        );

        $this->assertEquals('A-002', $ticket2->ticket_number);
        $this->assertEquals(2, $ticket2->sequence_number);

        $dailyQueue = DailyQueue::find($ticket1->daily_queue_id);
        $this->assertEquals(2, $dailyQueue->current_number);
    }

    public function test_online_and_walk_in_tickets_share_the_same_fifo_sequence_counter(): void
    {
        // 1. Online customer takes ticket
        $ticket1 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Online Customer 1',
            customerPhone: '081234567811'
        );

        // 2. Merchant registers walk-in customer
        $ticket2 = $this->queueService->generateWalkInTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Walk-in Customer 2',
            operator: $this->merchant
        );

        // 3. Online customer takes another ticket
        $ticket3 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Online Customer 3',
            customerPhone: '081234567813'
        );

        $this->assertEquals('A-001', $ticket1->ticket_number);
        $this->assertEquals(QueueTicket::SOURCE_ONLINE, $ticket1->source);

        $this->assertEquals('A-002', $ticket2->ticket_number);
        $this->assertEquals(QueueTicket::SOURCE_WALK_IN, $ticket2->source);

        $this->assertEquals('A-003', $ticket3->ticket_number);
        $this->assertEquals(QueueTicket::SOURCE_ONLINE, $ticket3->source);

        $dailyQueue = DailyQueue::find($ticket1->daily_queue_id);
        $this->assertEquals(3, $dailyQueue->current_number);
    }

    public function test_different_businesses_have_independent_ticket_sequences(): void
    {
        $category = BusinessCategory::first();
        $merchant2 = User::factory()->merchant()->create();
        $business2 = Business::create([
            'user_id' => $merchant2->id,
            'category_id' => $category->id,
            'name' => 'Salon Renon',
            'slug' => 'salon-renon',
            'phone' => '081987654321',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);
        $service2 = Service::create([
            'business_id' => $business2->id,
            'name' => 'Haircut',
            'duration_minutes' => 30,
            'daily_quota' => 20,
            'is_active' => true,
        ]);

        $ticketA1 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Customer Business 1',
            customerPhone: '081234567891'
        );

        $ticketB1 = $this->queueService->generateOnlineTicket(
            business: $business2,
            service: $service2,
            customerName: 'Customer Business 2',
            customerPhone: '081234567892'
        );

        $this->assertEquals('A-001', $ticketA1->ticket_number);
        $this->assertEquals('A-001', $ticketB1->ticket_number);
        $this->assertNotEquals($ticketA1->daily_queue_id, $ticketB1->daily_queue_id);
    }
}

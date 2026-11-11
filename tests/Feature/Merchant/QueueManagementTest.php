<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueStatusHistory;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueManagementTest extends TestCase
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
            'name' => 'Salon & Spa',
            'slug' => 'salon-spa',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Bali Cantik Salon',
            'slug' => 'bali-cantik-salon',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Creambath Tradisional',
            'duration_minutes' => 45,
            'price' => 75000,
            'is_active' => true,
        ]);

        $this->dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::now('Asia/Makassar')->toDateString(),
            'current_number' => 2,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);
    }

    public function test_merchant_can_view_queue_list(): void
    {
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Ni Wayan Sari',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-sari-01',
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.queues'));

        $response->assertOk();
        $response->assertSee('Meja Operasional Antrean');
        $response->assertSee('A-001');
        $response->assertSee('Ni Wayan Sari');
        $response->assertSee('Creambath Tradisional');
    }

    public function test_queue_list_can_be_searched_by_ticket_number_or_customer_name(): void
    {
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Made Suardana',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-suardana-1',
        ]);

        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Ketut Wijaya',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_WALK_IN,
            'access_token' => 'token-wijaya-2',
        ]);

        // Search by name
        $responseName = $this->actingAs($this->merchant)->get(route('merchant.queues', ['search' => 'Suardana']));
        $responseName->assertOk();
        $responseName->assertSee('Made Suardana');
        $responseName->assertDontSee('Ketut Wijaya');

        // Search by ticket number
        $responseNumber = $this->actingAs($this->merchant)->get(route('merchant.queues', ['search' => 'A-002']));
        $responseNumber->assertOk();
        $responseNumber->assertSee('Ketut Wijaya');
        $responseNumber->assertDontSee('Made Suardana');
    }

    public function test_queue_list_can_be_filtered_by_status(): void
    {
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Menunggu',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-menunggu',
        ]);

        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Pelanggan Selesai',
            'status' => QueueTicket::STATUS_SELESAI,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-selesai',
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.queues', ['status' => 'selesai']));

        $response->assertOk();
        $response->assertSee('Pelanggan Selesai');
        $response->assertDontSee('Pelanggan Menunggu');
    }

    public function test_queue_list_can_be_filtered_by_service_and_source(): void
    {
        $secondService = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Hair Spa Modern',
            'duration_minutes' => 60,
            'price' => 120000,
            'is_active' => true,
        ]);

        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Creambath Online',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-src-1',
        ]);

        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $secondService->id,
            'ticket_number' => 'B-001',
            'sequence_number' => 2,
            'customer_name' => 'Pelanggan Spa Walk-in',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_WALK_IN,
            'access_token' => 'token-src-2',
        ]);

        // Filter by service
        $resService = $this->actingAs($this->merchant)->get(route('merchant.queues', ['service_id' => $secondService->id]));
        $resService->assertOk();
        $resService->assertSee('Pelanggan Spa Walk-in');
        $resService->assertDontSee('Pelanggan Creambath Online');

        // Filter by source
        $resSource = $this->actingAs($this->merchant)->get(route('merchant.queues', ['source' => 'online']));
        $resSource->assertOk();
        $resSource->assertSee('Pelanggan Creambath Online');
        $resSource->assertDontSee('Pelanggan Spa Walk-in');
    }

    public function test_merchant_cannot_see_other_business_tickets_in_queue_list(): void
    {
        $otherMerchant = User::factory()->merchant()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherMerchant->id,
            'name' => 'Spa Lain Denpasar',
            'slug' => 'spa-lain-denpasar',
            'phone' => '087777777777',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);
        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Pijat Refleksi',
            'duration_minutes' => 30,
            'price' => 40000,
            'is_active' => true,
        ]);
        $otherDailyQueue = DailyQueue::create([
            'business_id' => $otherBusiness->id,
            'queue_date' => Carbon::now('Asia/Makassar')->toDateString(),
            'current_number' => 1,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);
        QueueTicket::create([
            'business_id' => $otherBusiness->id,
            'daily_queue_id' => $otherDailyQueue->id,
            'service_id' => $otherService->id,
            'ticket_number' => 'X-777',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Toko Sebelah',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-alien-777',
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.queues'));

        $response->assertOk();
        $response->assertDontSee('X-777');
        $response->assertDontSee('Pelanggan Toko Sebelah');
    }

    public function test_merchant_can_view_ticket_detail_with_audit_history(): void
    {
        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-010',
            'sequence_number' => 10,
            'customer_name' => 'Gede Ariasa',
            'customer_phone' => '081298765432',
            'notes' => 'Catatan khusus pelanggan',
            'status' => QueueTicket::STATUS_SELESAI,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-ariasa-10',
        ]);

        QueueStatusHistory::create([
            'queue_ticket_id' => $ticket->id,
            'event_type' => QueueStatusHistory::EVENT_STATUS_CHANGED,
            'from_status' => QueueTicket::STATUS_MENUNGGU,
            'to_status' => QueueTicket::STATUS_DIPANGGIL,
            'actor_type' => QueueStatusHistory::ACTOR_MERCHANT,
            'actor_id' => $this->merchant->id,
            'note' => 'Memanggil nomor antrean berikutnya (FIFO)',
            'created_at' => now()->subMinutes(15),
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.queues.show', $ticket));

        $response->assertOk();
        $response->assertSee('A-010');
        $response->assertSee('Gede Ariasa');
        $response->assertSee('081298765432');
        $response->assertSee('Catatan khusus pelanggan');
        $response->assertSee('Memanggil nomor antrean berikutnya (FIFO)');
    }

    public function test_merchant_cannot_view_ticket_belonging_to_another_business(): void
    {
        $otherMerchant = User::factory()->merchant()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherMerchant->id,
            'name' => 'Usaha Tetangga',
            'slug' => 'usaha-tetangga',
            'phone' => '088888888888',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);
        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Layanan Asing',
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
            'ticket_number' => 'ALIEN-01',
            'sequence_number' => 1,
            'customer_name' => 'Customer Rahasia',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-alien-show',
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.queues.show', $alienTicket));

        $response->assertForbidden();
    }

    public function test_merchant_can_view_qr_code_page(): void
    {
        $response = $this->actingAs($this->merchant)->get(route('merchant.queues.qr'));

        $response->assertOk();
        $response->assertSee('Tautan &amp; QR Code Antrean', false);
        $response->assertSee('bali-cantik-salon');
        $response->assertSee('<svg', false);
    }
}


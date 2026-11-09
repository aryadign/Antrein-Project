<?php

namespace Tests\Feature\Customer;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPollingTest extends TestCase
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
            'name' => 'Klinik Fisioterapi',
            'slug' => 'klinik-fisioterapi',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'FisioCare Bali',
            'slug' => 'fisiocare-bali',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Terapi Cedera Bahu',
            'duration_minutes' => 45,
            'daily_quota' => 20,
            'is_active' => true,
        ]);
    }

    public function test_tracking_page_contains_adaptive_polling_logic(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Komang Krisna',
            customerPhone: '081234567890'
        );

        $response = $this->get(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertSee('queueTracker');
        $response->assertSee('getPollInterval()');
        $response->assertSee('5000'); // 5 seconds for menunggu / dipanggil
        $response->assertSee('15000'); // 15 seconds for sedang_dilayani
        $response->assertSee('visibilitychange'); // Tab visibility awareness
    }

    public function test_polling_json_returns_dynamic_updates_when_status_changes(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Komang Krisna',
            customerPhone: '081234567890'
        );

        // 1. Initial status: menunggu
        $res1 = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));
        $res1->assertOk();
        $this->assertEquals(QueueTicket::STATUS_WAITING, $res1->json('data.status'));
        $this->assertEquals(1, $res1->json('data.queue_position'));
        $this->assertEquals(0, $res1->json('data.ahead_count'));

        // 2. Merchant calls ticket: dipanggil
        $this->queueService->callNext($this->business, $this->merchant);
        $res2 = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));
        $res2->assertOk();
        $this->assertEquals(QueueTicket::STATUS_CALLED, $res2->json('data.status'));
        $this->assertEquals('A-001', $res2->json('data.now_serving'));

        // 3. Merchant starts service: sedang_dilayani
        $ticket->refresh();
        $this->queueService->startService($ticket, $this->merchant);
        $res3 = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));
        $res3->assertOk();
        $this->assertEquals(QueueTicket::STATUS_SERVING, $res3->json('data.status'));

        // 4. Merchant completes service: selesai
        $ticket->refresh();
        $this->queueService->completeTicket($ticket, $this->merchant);
        $res4 = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));
        $res4->assertOk();
        $this->assertEquals(QueueTicket::STATUS_COMPLETED, $res4->json('data.status'));
    }

    public function test_polling_json_returns_410_gone_after_expiry_without_pii(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'Komang Krisna',
            customerPhone: '081234567890'
        );

        $this->queueService->callNext($this->business, $this->merchant);
        $ticket->refresh();
        $this->queueService->startService($ticket, $this->merchant);
        $ticket->refresh();
        $this->queueService->completeTicket($ticket, $this->merchant);

        // Advance 25 hours
        Carbon::setTestNow(now()->addHours(25));

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertStatus(410);
        $response->assertJson([
            'success' => false,
            'status' => 'expired',
        ]);
        $this->assertStringNotContainsString('Komang Krisna', $response->content());

        Carbon::setTestNow();
    }
}

<?php

namespace Tests\Feature\Queue;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackingTokenSecurityTest extends TestCase
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
            'name' => 'Faskes & Optik',
            'slug' => 'faskes-optik',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Optik Melati Denpasar',
            'slug' => 'optik-melati-denpasar',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Pemeriksaan Refraksi Mata',
            'duration_minutes' => 20,
            'daily_quota' => 40,
            'is_active' => true,
        ]);
    }

    public function test_access_token_is_secure_forty_character_string(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'I Made Wijaya',
            customerPhone: '081234567890'
        );

        $this->assertEquals(40, strlen($ticket->access_token));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{40}$/', $ticket->access_token);
    }

    public function test_public_track_endpoint_returns_ticket_data_with_masked_phone(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'I Made Wijaya',
            customerPhone: '081234567890'
        );

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'ticket_number' => 'A-001',
                'customer_name' => 'I Made Wijaya',
                'customer_phone_masked' => '081***890',
                'status' => QueueTicket::STATUS_WAITING,
                'business_name' => 'Optik Melati Denpasar',
                'service_name' => 'Pemeriksaan Refraksi Mata',
            ],
        ]);

        // Verify full unmasked phone is not leaked in json response
        $this->assertStringNotContainsString('081234567890', $response->content());
    }

    public function test_invalid_token_returns_404(): void
    {
        $fakeToken = Str::random(40);
        $response = $this->getJson(route('customer.queue.track', ['token' => $fakeToken]));

        $response->assertNotFound();
    }

    public function test_terminal_ticket_within_24_hours_remains_accessible(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'I Made Wijaya',
            customerPhone: '081234567890'
        );

        $this->queueService->callNext($this->business, $this->merchant);
        $ticket->refresh();
        $this->queueService->startService($ticket, $this->merchant);
        $ticket->refresh();
        $this->queueService->completeTicket($ticket, $this->merchant);
        $ticket->refresh();

        // Advance 12 hours
        Carbon::setTestNow(now()->addHours(12));

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));
        $response->assertOk();
        $this->assertEquals(QueueTicket::STATUS_COMPLETED, $response->json('data.status'));

        Carbon::setTestNow(); // reset
    }

    public function test_terminal_ticket_past_24_hours_returns_410_gone_without_pii(): void
    {
        $ticket = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->service,
            customerName: 'I Made Wijaya',
            customerPhone: '081234567890'
        );

        $this->queueService->callNext($this->business, $this->merchant);
        $ticket->refresh();
        $this->queueService->startService($ticket, $this->merchant);
        $ticket->refresh();
        $this->queueService->completeTicket($ticket, $this->merchant);
        $ticket->refresh();

        // Advance 25 hours past completion
        Carbon::setTestNow(now()->addHours(25));

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertStatus(410);
        $response->assertJson([
            'success' => false,
            'status' => 'expired',
        ]);

        // Assure no customer data leaked in 410 response
        $this->assertStringNotContainsString('I Made Wijaya', $response->content());

        Carbon::setTestNow(); // reset
    }
}

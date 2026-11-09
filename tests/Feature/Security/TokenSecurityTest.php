<?php

namespace Tests\Feature\Security;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private Service $service;
    private DailyQueue $dailyQueue;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Kesehatan',
            'slug' => 'kesehatan',
            'is_active' => true,
        ]);

        $merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Sehat',
            'slug' => 'klinik-sehat',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Konsultasi Dokter',
            'duration_minutes' => 20,
            'is_active' => true,
        ]);

        $this->dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::today('Asia/Makassar'),
            'current_number' => 1,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);
    }

    public function test_tracking_token_entropy_and_uniqueness(): void
    {
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $token = bin2hex(random_bytes(32));
            $this->assertEquals(64, strlen($token));
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
            $this->assertNotContains($token, $tokens);
            $tokens[] = $token;
        }
    }

    public function test_active_ticket_can_be_tracked_with_valid_token(): void
    {
        $token = bin2hex(random_bytes(32));

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_WAITING,
            'access_token' => $token,
        ]);

        $response = $this->get(route('customer.queue.track', ['token' => $token]));
        $response->assertOk();
        $response->assertSee('A-001');
        $response->assertSee('Budi Santoso');
    }

    public function test_terminal_ticket_older_than_24_hours_returns_410_gone_without_pii(): void
    {
        $token = bin2hex(random_bytes(32));

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Rahasia Pribadi',
            'customer_phone' => '081999888777',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_COMPLETED,
            'completed_at' => Carbon::now('Asia/Makassar')->subHours(25),
            'access_token' => $token,
        ]);

        $response = $this->get(route('customer.queue.track', ['token' => $token]));
        $response->assertStatus(410);
        $response->assertDontSee('Rahasia Pribadi');
        $response->assertDontSee('081999888777');
    }
}

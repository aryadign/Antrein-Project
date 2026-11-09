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
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitingSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Business $business;
    private Service $service;
    private DailyQueue $dailyQueue;
    private QueueTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clearResolvedInstances();
        RateLimiter::clear('forgot-password|127.0.0.1');
        RateLimiter::clear('queue-track-poll|127.0.0.1');

        $category = BusinessCategory::create([
            'name' => 'F&B',
            'slug' => 'f-and-b',
            'is_active' => true,
        ]);

        $this->user = User::factory()->merchant()->create([
            'email' => 'merchant@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->business = Business::create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'name' => 'Warung Kopi Rate Limit',
            'slug' => 'warung-kopi-rate-limit',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Hitam',
            'duration_minutes' => 10,
            'is_active' => true,
        ]);

        $this->dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::today('Asia/Makassar'),
            'current_number' => 1,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        $this->ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan 1',
            'customer_phone' => '081234567890',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_WAITING,
            'access_token' => bin2hex(random_bytes(32)),
        ]);
    }

    public function test_password_reset_throttling_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('password.email'), [
                'email' => 'test@example.com',
            ]);
            $response->assertSessionHas('status');
        }

        // 6th attempt should hit rate limit
        $response = $this->post(route('password.email'), [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_does_not_enumerate_users(): void
    {
        RateLimiter::clear('forgot-password|127.0.0.1');

        $expectedMessage = 'Jika alamat email Anda terdaftar, kami telah mengirimkan tautan untuk mengatur ulang kata sandi Anda.';

        $existingUserResponse = $this->post(route('password.email'), [
            'email' => 'merchant@example.com',
        ]);
        $existingUserResponse->assertSessionHas('status', $expectedMessage);

        RateLimiter::clear('forgot-password|127.0.0.1');

        $nonExistingUserResponse = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);
        $nonExistingUserResponse->assertSessionHas('status', $expectedMessage);
    }

    public function test_customer_ticket_tracking_polling_rate_limiting(): void
    {
        $token = $this->ticket->access_token;

        for ($i = 0; $i < 60; $i++) {
            $response = $this->get(route('customer.queue.track', ['token' => $token]));
            $response->assertOk();
        }

        // 61st attempt within 1 minute should return 429 Too Many Requests
        $response = $this->get(route('customer.queue.track', ['token' => $token]));
        $response->assertStatus(429);
        $this->assertTrue($response->headers->has('Retry-After'));
    }

    public function test_login_throttles_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => 'merchant@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login'), [
            'email' => 'merchant@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
    }
}

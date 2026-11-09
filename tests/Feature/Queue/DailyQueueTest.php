<?php

namespace Tests\Feature\Queue;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\User;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyQueueTest extends TestCase
{
    use RefreshDatabase;

    private QueueService $queueService;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueService = app(QueueService::class);

        $category = BusinessCategory::create([
            'name' => 'Klinik & Salon',
            'slug' => 'klinik-salon',
            'is_active' => true,
        ]);

        $merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Denpasar Sehat',
            'slug' => 'klinik-denpasar-sehat',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);
    }

    public function test_get_or_create_daily_queue_creates_new_queue_with_zero_current_number(): void
    {
        $queue = $this->queueService->getOrCreateDailyQueue($this->business);

        $this->assertInstanceOf(DailyQueue::class, $queue);
        $this->assertEquals($this->business->id, $queue->business_id);
        $this->assertEquals(0, $queue->current_number);
        $this->assertEquals(DailyQueue::STATUS_ACTIVE, $queue->status);
        $this->assertDatabaseHas('daily_queues', [
            'id' => $queue->id,
            'business_id' => $this->business->id,
            'current_number' => 0,
        ]);
    }

    public function test_daily_queue_resolves_date_in_business_timezone(): void
    {
        // 2026-11-09 23:30:00 UTC is 2026-11-10 07:30:00 in Asia/Makassar (UTC+8)
        Carbon::setTestNow(Carbon::parse('2026-11-09 23:30:00', 'UTC'));

        $queue = $this->queueService->getOrCreateDailyQueue($this->business);

        $this->assertEquals('2026-11-10', $queue->queue_date->toDateString());

        Carbon::setTestNow(); // reset
    }

    public function test_get_or_create_daily_queue_is_idempotent_and_returns_existing(): void
    {
        $queue1 = $this->queueService->getOrCreateDailyQueue($this->business, '2026-11-09');
        $queue1->update(['current_number' => 5]);

        $queue2 = $this->queueService->getOrCreateDailyQueue($this->business, '2026-11-09');

        $this->assertEquals($queue1->id, $queue2->id);
        $this->assertEquals(5, $queue2->current_number);
        $this->assertEquals(1, DailyQueue::where('business_id', $this->business->id)->whereDate('queue_date', '2026-11-09')->count());
    }

    public function test_different_businesses_have_independent_daily_queues_on_same_date(): void
    {
        $category = BusinessCategory::first();
        $merchant2 = User::factory()->merchant()->create();
        $business2 = Business::create([
            'user_id' => $merchant2->id,
            'category_id' => $category->id,
            'name' => 'Barbershop Kuta',
            'slug' => 'barbershop-kuta',
            'phone' => '081298765432',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $queue1 = $this->queueService->getOrCreateDailyQueue($this->business, '2026-11-09');
        $queue2 = $this->queueService->getOrCreateDailyQueue($business2, '2026-11-09');

        $this->assertNotEquals($queue1->id, $queue2->id);
        $this->assertEquals($this->business->id, $queue1->business_id);
        $this->assertEquals($business2->id, $queue2->business_id);
    }
}

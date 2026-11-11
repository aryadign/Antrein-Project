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

class QuotaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private QueueService $queueService;
    private User $merchant;
    private Business $business;
    private Service $limitedService;
    private Service $unlimitedService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueService = app(QueueService::class);

        $category = BusinessCategory::create([
            'name' => 'Klinik Gigi',
            'slug' => 'klinik-gigi',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Dental Care Sanur',
            'slug' => 'dental-care-sanur',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        // Service with strict quota of 2
        $this->limitedService = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Pembersihan Karang Gigi (Scaling)',
            'duration_minutes' => 45,
            'daily_quota' => 2,
            'is_active' => true,
        ]);

        // Service with unlimited quota
        $this->unlimitedService = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Konsultasi Online',
            'duration_minutes' => 15,
            'daily_quota' => null,
            'is_active' => true,
        ]);
    }

    public function test_tickets_can_be_created_until_quota_is_exhausted(): void
    {
        // 1st ticket
        $t1 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->limitedService,
            customerName: 'Patient 1',
            customerPhone: '081234567001'
        );
        $this->assertEquals('A-001', $t1->ticket_number);

        // 2nd ticket (hits quota limit of 2)
        $t2 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->limitedService,
            customerName: 'Patient 2',
            customerPhone: '081234567002'
        );
        $this->assertEquals('A-002', $t2->ticket_number);

        // 3rd ticket should be rejected via online endpoint
        $response = $this->postJson(route('customer.queue.take', ['slug' => $this->business->slug]), [
            'service_id' => $this->limitedService->id,
            'customer_name' => 'Patient 3',
            'customer_phone' => '081234567003',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('telah habis', $response->json('message'));

        // Also rejected via walk-in
        $walkInResponse = $this->actingAs($this->merchant)->postJson(route('merchant.queues.walk-in'), [
            'service_id' => $this->limitedService->id,
            'customer_name' => 'Patient 3 Walk-in',
        ]);
        $walkInResponse->assertStatus(422);
        $walkInResponse->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('telah habis', $walkInResponse->json('message'));
    }

    public function test_quota_is_consumed_by_active_and_completed_statuses_but_freed_by_cancelled(): void
    {
        $dailyQueue = $this->queueService->getOrCreateDailyQueue($this->business);

        // Create 2 tickets
        $t1 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->limitedService,
            customerName: 'Patient 1',
            customerPhone: '081234567001'
        );
        $t2 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->limitedService,
            customerName: 'Patient 2',
            customerPhone: '081234567002'
        );

        // Advance t1 to selesai
        $this->queueService->callNext($this->business, $this->merchant);
        $t1->refresh();
        $this->queueService->startService($t1, $this->merchant);
        $t1->refresh();
        $this->queueService->completeTicket($t1, $this->merchant);
        $t1->refresh();

        // Quota is still 2 consumed (1 selesai, 1 menunggu)
        $this->assertEquals(2, $this->queueService->getConsumedDailyQuotaCount($this->limitedService, $dailyQueue->id));

        // Now cancel t2 (patient cancels)
        $this->queueService->cancelTicket($t2, $this->merchant, 'Batalkan janji temu');

        // Quota is now 1 consumed (1 selesai, 1 dibatalkan is excluded)
        $this->assertEquals(1, $this->queueService->getConsumedDailyQuotaCount($this->limitedService, $dailyQueue->id));

        // A new patient can now take a ticket!
        $t3 = $this->queueService->generateOnlineTicket(
            business: $this->business,
            service: $this->limitedService,
            customerName: 'Patient 3',
            customerPhone: '081234567003'
        );

        $this->assertEquals('A-003', $t3->ticket_number);
    }

    public function test_unlimited_service_has_no_quota_restriction(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $t = $this->queueService->generateOnlineTicket(
                business: $this->business,
                service: $this->unlimitedService,
                customerName: "Unlimited Patient {$i}",
                customerPhone: "08123456700{$i}"
            );
            $this->assertEquals("A-00{$i}", $t->ticket_number);
        }

        $this->assertEquals(5, QueueTicket::where('service_id', $this->unlimitedService->id)->count());
    }
}

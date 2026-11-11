<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\MerchantReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;
    private Service $serviceA;
    private Service $serviceB;
    private DailyQueue $dailyQueue;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Barbershop',
            'slug' => 'barbershop-test',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Gentleman Cuts Bali',
            'slug' => 'gentleman-cuts-bali',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_active' => true,
        ]);

        $this->serviceA = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Gentleman Haircut',
            'duration_minutes' => 30,
            'price' => 75000,
            'is_active' => true,
        ]);

        $this->serviceB = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Beard Grooming',
            'duration_minutes' => 20,
            'price' => 50000,
            'is_active' => true,
        ]);

        $this->dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::today('Asia/Makassar')->toDateString(),
            'total_queue' => 0,
            'current_number' => 0,
            'is_open' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('merchant.reports'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_merchant_reports(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('merchant.reports'));
        $response->assertForbidden();
    }

    public function test_merchant_without_onboarding_is_redirected_to_onboarding(): void
    {
        $newMerchant = User::factory()->merchant()->create();

        $response = $this->actingAs($newMerchant)->get(route('merchant.reports'));
        $response->assertRedirect(route('merchant.onboarding'));
    }

    public function test_tenant_isolation_ensures_merchant_only_sees_own_business_data(): void
    {
        // Other business
        $otherCategory = BusinessCategory::first();
        $otherMerchant = User::factory()->merchant()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherMerchant->id,
            'category_id' => $otherCategory->id,
            'name' => 'Salon Lain',
            'slug' => 'salon-lain',
            'phone' => '081999999999',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_active' => true,
        ]);
        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Layanan Salon Lain',
            'duration_minutes' => 45,
            'is_active' => true,
        ]);
        $otherQueue = DailyQueue::create([
            'business_id' => $otherBusiness->id,
            'queue_date' => Carbon::today('Asia/Makassar')->toDateString(),
            'total_queue' => 1,
            'current_number' => 0,
            'is_open' => true,
        ]);

        // Ticket in other business
        QueueTicket::create([
            'business_id' => $otherBusiness->id,
            'daily_queue_id' => $otherQueue->id,
            'service_id' => $otherService->id,
            'ticket_number' => 'Z-999',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Rahasia Lain',
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => 'tok-other-999',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.reports'));

        $response->assertOk();
        $response->assertViewIs('merchant.reports.index');
        $response->assertDontSee('Layanan Salon Lain');
        $response->assertDontSee('Pelanggan Rahasia Lain');
    }

    public function test_period_filters_and_invalid_date_validation(): void
    {
        // 1. Valid periods
        foreach (['today', '7_days', '30_days'] as $period) {
            $response = $this->actingAs($this->merchant)->get(route('merchant.reports', ['period' => $period]));
            $response->assertOk();
        }

        // 2. Custom valid date range
        $response = $this->actingAs($this->merchant)->get(route('merchant.reports', [
            'period' => 'custom',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-08',
        ]));
        $response->assertOk();

        // 3. Custom invalid date range: start_date > end_date
        $invalidResponse = $this->actingAs($this->merchant)->get(route('merchant.reports', [
            'period' => 'custom',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-01',
        ]));
        $invalidResponse->assertRedirect(route('merchant.reports', ['period' => '7_days']));
        $invalidResponse->assertSessionHasErrors('date_range');
    }

    public function test_known_answer_dataset_metrics_aggregation_reconciliation(): void
    {
        /**
         * Known-answer dataset test (M9-12):
         *
         * Ticket 1: Service A, Created at T, Started at T+10m, Completed at T+30m => Waiting = 10m (600s), Service = 20m (1200s), Status Selesai
         * Ticket 2: Service A, Created at T+15m, Started at T+35m, Completed at T+65m => Waiting = 20m (1200s), Service = 30m (1800s), Status Selesai
         * Ticket 3: Service B, Created at T+30m, Started at T+60m, Completed at T+80m => Waiting = 30m (1800s), Service = 20m (1200s), Status Selesai
         * Ticket 4: Service B, Created at T+40m, never started, Skipped at T+50m => Status Dilewati
         * Ticket 5: Service A, Created at T+50m, never started, Cancelled at T+55m => Status Dibatalkan
         *
         * Expected Totals:
         * Total tickets created: 5
         * Completed tickets: 3
         * Skipped tickets: 1
         * Cancelled tickets: 1
         * Waiting tickets sample: 3 tickets (T1: 10m, T2: 20m, T3: 30m) => Avg Waiting = (10 + 20 + 30) / 3 = 20.0 minutes
         * Service duration sample: 3 tickets (T1: 20m, T2: 30m, T3: 20m) => Avg Service = (20 + 30 + 20) / 3 = 23.3 minutes
         * Service A: Total 3 (2 completed, 0 skipped, 1 cancelled)
         * Service B: Total 2 (1 completed, 1 skipped, 0 cancelled)
         */

        $baseTime = Carbon::now('Asia/Makassar')->startOfDay()->addHours(9);

        // Ticket 1
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan 1',
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => 'tok-ka-1',
            'created_at' => $baseTime->copy(),
            'service_started_at' => $baseTime->copy()->addMinutes(10),
            'completed_at' => $baseTime->copy()->addMinutes(30),
        ]);

        // Ticket 2
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Pelanggan 2',
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => 'tok-ka-2',
            'created_at' => $baseTime->copy()->addMinutes(15),
            'service_started_at' => $baseTime->copy()->addMinutes(35),
            'completed_at' => $baseTime->copy()->addMinutes(65),
        ]);

        // Ticket 3
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->serviceB->id,
            'ticket_number' => 'B-001',
            'sequence_number' => 3,
            'customer_name' => 'Pelanggan 3',
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => 'tok-ka-3',
            'created_at' => $baseTime->copy()->addMinutes(30),
            'service_started_at' => $baseTime->copy()->addMinutes(60),
            'completed_at' => $baseTime->copy()->addMinutes(80),
        ]);

        // Ticket 4 (Skipped)
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->serviceB->id,
            'ticket_number' => 'B-002',
            'sequence_number' => 4,
            'customer_name' => 'Pelanggan 4',
            'status' => QueueTicket::STATUS_DILEWATI,
            'access_token' => 'tok-ka-4',
            'created_at' => $baseTime->copy()->addMinutes(40),
            'skipped_at' => $baseTime->copy()->addMinutes(50),
        ]);

        // Ticket 5 (Cancelled)
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-003',
            'sequence_number' => 5,
            'customer_name' => 'Pelanggan 5',
            'status' => QueueTicket::STATUS_DIBATALKAN,
            'access_token' => 'tok-ka-5',
            'created_at' => $baseTime->copy()->addMinutes(50),
            'cancelled_at' => $baseTime->copy()->addMinutes(55),
        ]);

        /** @var MerchantReportService $service */
        $service = app(MerchantReportService::class);
        $report = $service->getReportData($this->business, 'today');

        // Verify Total Tickets includes cancelled and skipped (M9-02)
        $this->assertEquals(5, $report['totalTickets']);

        // Verify Terminal Status Counts are separate (M9-03)
        $this->assertEquals(3, $report['completedCount']);
        $this->assertEquals(1, $report['skippedCount']);
        $this->assertEquals(1, $report['cancelledCount']);

        // Verify Average Waiting Time is exactly 20.0 minutes (M9-04)
        $this->assertEquals(20.0, $report['avgWaitingMinutes']);
        $this->assertEquals(3, $report['waitingSamples']);

        // Verify Average Service Duration is exactly 23.3 minutes (M9-05)
        $this->assertEquals(23.3, $report['avgServiceMinutes']);
        $this->assertEquals(3, $report['serviceSamples']);

        // Verify Per-Service Breakdown (M9-08)
        $services = $report['servicesBreakdown'];
        $svcA = $services->firstWhere('service_name', 'Gentleman Haircut');
        $this->assertNotNull($svcA);
        $this->assertEquals(3, $svcA['total_tickets']);
        $this->assertEquals(2, $svcA['completed_tickets']);
        $this->assertEquals(1, $svcA['cancelled_tickets']);

        $svcB = $services->firstWhere('service_name', 'Beard Grooming');
        $this->assertNotNull($svcB);
        $this->assertEquals(2, $svcB['total_tickets']);
        $this->assertEquals(1, $svcB['completed_tickets']);
        $this->assertEquals(1, $svcB['skipped_tickets']);

        // Web view response verification
        $response = $this->actingAs($this->merchant)->get(route('merchant.reports', ['period' => 'today']));
        $response->assertOk();
        $response->assertSeeText('20 mnt');
        $response->assertSeeText('23.3 mnt');
        $response->assertSee('Gentleman Haircut');
        $response->assertSee('Beard Grooming');
    }

    public function test_empty_and_incomplete_data_displays_insufficient_data_notice(): void
    {
        // Business has 0 tickets created
        $response = $this->actingAs($this->merchant)->get(route('merchant.reports', ['period' => 'today']));

        $response->assertOk();
        $response->assertSee('Belum cukup data');
        $response->assertSee('Belum Ada Data Antrean');
        $response->assertDontSee('0.0 mnt');
    }

    public function test_peak_hour_threshold_behavior_oq15(): void
    {
        /** @var MerchantReportService $service */
        $service = app(MerchantReportService::class);

        // Case 1: Max tickets in 1-hour bucket < 50 => threshold_met is FALSE
        $time10am = Carbon::now('Asia/Makassar')->startOfDay()->addHours(10)->addMinutes(15);
        for ($i = 0; $i < 10; $i++) {
            QueueTicket::create([
                'business_id' => $this->business->id,
                'daily_queue_id' => $this->dailyQueue->id,
                'service_id' => $this->serviceA->id,
                'ticket_number' => 'P-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'sequence_number' => $i + 1,
                'customer_name' => 'Pelanggan ' . ($i + 1),
                'status' => QueueTicket::STATUS_SELESAI,
                'access_token' => 'tok-p-' . $i,
                'created_at' => $time10am->copy(),
            ]);
        }

        $reportBelow = $service->getReportData($this->business, 'today');
        $this->assertFalse($reportBelow['peakHour']['threshold_met']);
        $this->assertEquals(10, $reportBelow['peakHour']['max_count']);
        $this->assertNull($reportBelow['peakHour']['peak_range']);

        // Case 2: Create 40 more tickets in the same 10:00-11:00 bucket => Total 50 => threshold_met is TRUE (OQ-15)
        for ($i = 10; $i < 50; $i++) {
            QueueTicket::create([
                'business_id' => $this->business->id,
                'daily_queue_id' => $this->dailyQueue->id,
                'service_id' => $this->serviceA->id,
                'ticket_number' => 'P-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'sequence_number' => $i + 1,
                'customer_name' => 'Pelanggan ' . ($i + 1),
                'status' => QueueTicket::STATUS_SELESAI,
                'access_token' => 'tok-p-' . $i,
                'created_at' => $time10am->copy(),
            ]);
        }

        $reportMet = $service->getReportData($this->business, 'today');
        $this->assertTrue($reportMet['peakHour']['threshold_met']);
        $this->assertEquals(50, $reportMet['peakHour']['max_count']);
        $this->assertEquals('10:00 – 11:00', $reportMet['peakHour']['peak_range']);
    }

    public function test_inactive_services_with_historical_data_remain_visible_in_report(): void
    {
        $inactiveService = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Paket Lebaran Spesial (Nonaktif)',
            'duration_minutes' => 60,
            'is_active' => false,
        ]);

        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $this->dailyQueue->id,
            'service_id' => $inactiveService->id,
            'ticket_number' => 'L-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Lama',
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => 'tok-inactive-1',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.reports', ['period' => 'today']));
        $response->assertOk();
        $response->assertSee('Paket Lebaran Spesial (Nonaktif)');
        $response->assertSee('Nonaktif');
    }
}

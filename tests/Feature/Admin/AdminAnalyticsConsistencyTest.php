<?php

namespace Tests\Feature\Admin;

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

class AdminAnalyticsConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_tickets_definition_and_anti_duplicate_aggregation(): void
    {
        $admin = User::factory()->admin()->create();

        $category = BusinessCategory::create([
            'name' => 'Klinik',
            'slug' => 'klinik-analisis',
            'is_active' => true,
        ]);

        $merchant = User::factory()->merchant()->create();
        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Sehat Denpasar',
            'slug' => 'klinik-sehat-denpasar',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka 10',
            'timezone' => 'Asia/Makassar',
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Pemeriksaan Medis',
            'duration_minutes' => 20,
            'is_active' => true,
        ]);

        $dailyQueue = DailyQueue::create([
            'business_id' => $business->id,
            'queue_date' => Carbon::today('Asia/Makassar')->toDateString(),
            'total_queue' => 2,
            'current_number' => 0,
            'is_open' => true,
        ]);

        // Ticket 1: Has 4 status histories (Menunggu -> Dipanggil -> Dilayani -> Selesai)
        $ticket1 = QueueTicket::create([
            'business_id' => $business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pasien 1',
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => 'tok-audit-1',
            'created_at' => now(),
            'service_started_at' => now(),
            'completed_at' => now(),
        ]);

        foreach (['menunggu', 'dipanggil', 'sedang_dilayani', 'selesai'] as $st) {
            QueueStatusHistory::create([
                'queue_ticket_id' => $ticket1->id,
                'event_type' => QueueStatusHistory::EVENT_STATUS_CHANGED,
                'to_status' => $st,
                'created_at' => now(),
            ]);
        }

        // Ticket 2: Cancelled ticket
        $ticket2 = QueueTicket::create([
            'business_id' => $business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $service->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Pasien 2',
            'status' => QueueTicket::STATUS_DIBATALKAN,
            'access_token' => 'tok-audit-2',
            'created_at' => now(),
            'cancelled_at' => now(),
        ]);

        QueueStatusHistory::create([
            'queue_ticket_id' => $ticket2->id,
            'event_type' => QueueStatusHistory::EVENT_TICKET_CANCELLED,
            'to_status' => QueueTicket::STATUS_DIBATALKAN,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'today']));

        $response->assertOk();
        $viewData = $response->viewData('periodMetrics');

        // Total tickets MUST be exactly 2 despite having 5 history rows across the tickets (Anti-duplicate M9-14)
        $this->assertEquals(2, $viewData['tickets_count']);
        $this->assertEquals(1, $viewData['completed_count']);
        $this->assertEquals(1, $viewData['cancelled_count']);

        // Sum of status distribution must equal total period tickets
        $statusDist = $response->viewData('statusDistribution');
        $sumStatus = array_sum($statusDist);
        $this->assertEquals(2, $sumStatus);
    }

    public function test_consistent_date_filtering_across_metrics_and_trends(): void
    {
        $admin = User::factory()->admin()->create();

        // 7_days period check
        $response7 = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => '7_days']));
        $response7->assertOk();
        $this->assertEquals('7_days', $response7->viewData('period'));

        // 30_days period check
        $response30 = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => '30_days']));
        $response30->assertOk();
        $this->assertEquals('30_days', $response30->viewData('period'));

        // 'all' period check
        $responseAll = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => 'all']));
        $responseAll->assertOk();
        $this->assertEquals('all', $responseAll->viewData('period'));
    }
}


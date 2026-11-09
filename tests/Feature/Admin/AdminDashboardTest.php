<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_merchant_cannot_access_admin_dashboard(): void
    {
        $merchant = User::factory()->merchant()->create();

        $response = $this->actingAs($merchant)->get(route('admin.dashboard'));
        $response->assertForbidden();
    }

    public function test_admin_can_access_dashboard_with_all_metrics(): void
    {
        $admin = User::factory()->admin()->create();

        $category = BusinessCategory::create([
            'name' => 'Klinik',
            'slug' => 'klinik',
            'is_active' => true,
        ]);

        $merchant = User::factory()->merchant()->create();
        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Sehat',
            'slug' => 'klinik-sehat',
            'phone' => '081234567890',
            'address' => 'Jl. Sudirman 1',
            'timezone' => 'Asia/Makassar',
            'is_active' => true,
            'is_suspended' => false,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Konsultasi Dokter',
            'duration_minutes' => 15,
            'is_active' => true,
        ]);

        $dailyQueue = DailyQueue::create([
            'business_id' => $business->id,
            'queue_date' => Carbon::today('Asia/Makassar')->toDateString(),
            'total_queue' => 1,
            'current_number' => 0,
            'is_open' => true,
        ]);

        QueueTicket::create([
            'daily_queue_id' => $dailyQueue->id,
            'business_id' => $business->id,
            'service_id' => $service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Andi Wijaya',
            'customer_phone' => '081234567891',
            'source' => 'online',
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => 'token-test-123456',
        ]);

        ActivityLog::log(
            action: 'test_action',
            description: 'Aktivitas uji coba sistem dashboard',
            business: $business,
            user: $admin
        );

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('totalBusinesses');
        $response->assertViewHas('chartLabels');
        $response->assertViewHas('recentActivities');
        $response->assertSee('Klinik Sehat');
        $response->assertSee('Aktivitas uji coba sistem dashboard');
    }

    public function test_dashboard_filters_period_properly(): void
    {
        $admin = User::factory()->admin()->create();

        $periods = ['today', '7_days', '30_days', 'this_month', 'all'];

        foreach ($periods as $period) {
            $response = $this->actingAs($admin)->get(route('admin.dashboard', ['period' => $period]));
            $response->assertOk();
            $response->assertViewHas('period', $period);
        }
    }

    public function test_empty_state_renders_cleanly_without_division_by_zero(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('0');
        $response->assertSee('Belum ada log aktivitas tercatat');
    }
}


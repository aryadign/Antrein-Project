<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Klinik Kesehatan',
            'slug' => 'klinik-kesehatan',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Sehat Denpasar',
            'slug' => 'klinik-sehat-denpasar',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Pemeriksaan Umum',
            'duration_minutes' => 20,
            'price' => 50000,
            'is_active' => true,
        ]);

        BusinessHour::create([
            'business_id' => $this->business->id,
            'day_of_week' => 1,
            'open_time' => '08:00',
            'close_time' => '20:00',
            'is_closed' => false,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get(route('merchant.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_merchant_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('merchant.dashboard'));

        $response->assertForbidden();
    }

    public function test_non_onboarded_merchant_is_redirected_to_onboarding(): void
    {
        $freshMerchant = User::factory()->merchant()->create();

        $response = $this->actingAs($freshMerchant)->get(route('merchant.dashboard'));

        $response->assertRedirect(route('merchant.onboarding'));
    }

    public function test_merchant_can_view_operational_dashboard_with_accurate_metrics(): void
    {
        $dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::now('Asia/Makassar')->toDateString(),
            'current_number' => 5,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        // Create tickets across different statuses
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Selesai',
            'status' => QueueTicket::STATUS_SELESAI,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-selesai-1',
        ]);

        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Pelanggan Sedang Dilayani',
            'status' => QueueTicket::STATUS_SEDANG_DILAYANI,
            'source' => QueueTicket::SOURCE_WALK_IN,
            'access_token' => 'token-serving-2',
            'called_at' => now(),
            'service_started_at' => now(),
        ]);

        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-003',
            'sequence_number' => 3,
            'customer_name' => 'Pelanggan Menunggu',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-menunggu-3',
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.dashboard'));

        $response->assertOk();
        $response->assertSee('Klinik Sehat Denpasar');
        $response->assertSee('Pelanggan Sedang Dilayani');
        $response->assertSee('A-002');
        $response->assertSee('Sedang Berlangsung');
        $response->assertSee('Asia/Makassar');
    }

    public function test_dashboard_displays_status_notices_when_business_is_jeda_or_tutup(): void
    {
        $this->business->update(['status' => Business::STATUS_JEDA]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.dashboard'));

        $response->assertOk();
        $response->assertSee('Operasional Antrean Sedang DIJEDA');

        $this->business->update(['status' => Business::STATUS_TUTUP]);
        $this->merchant->unsetRelation('business');

        $response = $this->actingAs($this->merchant)->get(route('merchant.dashboard'));

        $response->assertOk();
        $response->assertSee('Operasional Antrean Sedang TUTUP');
    }

    public function test_dashboard_displays_suspended_notice_when_business_is_suspended(): void
    {
        $this->business->update(['is_suspended' => true]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.dashboard'));

        $response->assertOk();
        $response->assertSee('Akun Usaha Ditangguhkan (Suspended)');
    }

    public function test_dashboard_strictly_scopes_metrics_and_tickets_to_current_business(): void
    {
        // Another business with tickets
        $otherMerchant = User::factory()->merchant()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherMerchant->id,
            'name' => 'Usaha Lain Beda Tenant',
            'slug' => 'usaha-lain-beda-tenant',
            'phone' => '089999999999',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);
        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Layanan Asing',
            'duration_minutes' => 15,
            'price' => 10000,
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
            'ticket_number' => 'Z-999',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Rahasia Asing',
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-asing-999',
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Z-999');
        $response->assertDontSee('Pelanggan Rahasia Asing');
        $response->assertDontSee('Usaha Lain Beda Tenant');
    }
}

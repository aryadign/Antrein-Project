<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\BusinessMembership;
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

    private User $operator;

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

        $this->operator = User::factory()->merchant()->create();
        BusinessMembership::create([
            'business_id' => $this->business->id,
            'organization_id' => $this->business->organization_id,
            'user_id' => $this->operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
            'joined_at' => now(),
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

        // Operator sees single-branch live operational desk & Layar TV shortcut
        $operatorResponse = $this->actingAs($this->operator)->get(route('merchant.dashboard'));

        $operatorResponse->assertOk();
        $operatorResponse->assertSee('Klinik Sehat Denpasar');
        $operatorResponse->assertSee('Pelanggan Sedang Dilayani');
        $operatorResponse->assertSee('A-002');
        $operatorResponse->assertSee('Sedang Berlangsung');
        $operatorResponse->assertSee('Layar TV');
        $operatorResponse->assertSee('Asia/Makassar');

        // Owner sees ONLY combined organization report (no live desk / Now Serving / active ticket list)
        $ownerResponse = $this->actingAs($this->merchant)->get(route('merchant.dashboard'));

        $ownerResponse->assertOk();
        $ownerResponse->assertSee('Klinik Sehat Denpasar');
        $ownerResponse->assertSee('Ringkasan Gabungan Organisasi');
        $ownerResponse->assertSee('Laporan Gabungan Seluruh Cabang');
        $ownerResponse->assertDontSee('Sedang Berlangsung');
        $ownerResponse->assertDontSee('Antrean Aktif Berjalan');
        $ownerResponse->assertDontSee('A-002');
    }

    public function test_dashboard_displays_status_notices_when_business_is_jeda_or_tutup(): void
    {
        $this->business->update(['status' => Business::STATUS_JEDA]);

        $response = $this->actingAs($this->operator)->get(route('merchant.dashboard'));

        $response->assertOk();
        $response->assertSee('Operasional Antrean Sedang DIJEDA');

        $this->business->update(['status' => Business::STATUS_TUTUP]);

        $response = $this->actingAs($this->operator->fresh())->get(route('merchant.dashboard'));

        $response->assertOk();
        $response->assertSee('Operasional Antrean Sedang TUTUP');
    }

    public function test_dashboard_displays_suspended_notice_when_business_is_suspended(): void
    {
        $this->business->update(['is_suspended' => true]);

        $ownerResponse = $this->actingAs($this->merchant)->get(route('merchant.dashboard'));
        $ownerResponse->assertOk();
        $ownerResponse->assertSee('Akun Usaha Ditangguhkan (Suspended)');

        $operatorResponse = $this->actingAs($this->operator)->get(route('merchant.dashboard'));
        $operatorResponse->assertOk();
        $operatorResponse->assertSee('Akun Usaha Ditangguhkan (Suspended)');
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

    public function test_operator_dashboard_hides_branch_switcher_and_review_navigation(): void
    {
        $response = $this->actingAs($this->operator)->get(route('merchant.dashboard'));

        $response->assertOk();
        $response->assertSee('Layar TV');
        $response->assertSee('QR Antrean');
        $response->assertDontSee('Ganti Cabang');
        $response->assertDontSee(route('merchant.branches.index'), false);
        $response->assertDontSee(route('merchant.reviews.index'), false);
    }

    public function test_owner_dashboard_renders_laporan_ui_with_period_filters_and_charts(): void
    {
        $dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::now('Asia/Makassar')->toDateString(),
            'current_number' => 1,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Laporan',
            'status' => QueueTicket::STATUS_SELESAI,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'token-laporan-1',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.dashboard', ['period' => '30_days']));

        $response->assertOk()
            ->assertSee('Pilih Periode Ringkasan')
            ->assertSee('Total Tiket Dibuat')
            ->assertSee('Estimasi Pemasukan Kotor')
            ->assertSee('Rp 50.000')
            ->assertSee('Tren Volume Antrean Harian')
            ->assertSee('Distribusi Status Antrean')
            ->assertSee('Rincian Performa Per Cabang')
            ->assertSee('id="queueTrendChart"', false)
            ->assertSee('id="statusDistributionChart"', false)
            ->assertDontSee('A-001');

        $customToday = Carbon::now('Asia/Makassar')->toDateString();
        $customResponse = $this->actingAs($this->merchant)->get(route('merchant.dashboard', [
            'period' => 'custom',
            'start_date' => $customToday,
            'end_date' => $customToday,
        ]));
        $customResponse->assertOk()->assertSee('Tren Volume Antrean Harian');

        $invalidCustom = $this->actingAs($this->merchant)->get(route('merchant.dashboard', [
            'period' => 'custom',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-10',
        ]));
        $invalidCustom->assertRedirect(route('merchant.dashboard', ['period' => '7_days']));
    }

    public function test_owner_sidebar_and_beranda_hide_laporan_grup_and_organisasi_to_reduce_redundancy(): void
    {
        $ownerResponse = $this->actingAs($this->merchant)->get(route('merchant.dashboard'));

        $ownerResponse->assertOk()
            ->assertDontSee('Laporan Grup')
            ->assertDontSee('<span>Organisasi</span>', false)
            ->assertDontSee(route('merchant.organization.reports'), false)
            ->assertDontSee(route('merchant.organization.edit'), false)
            ->assertDontSee('QR Code Toko');

        $branchesResponse = $this->actingAs($this->merchant)->get(route('merchant.branches.index'));

        $branchesResponse->assertOk()
            ->assertSee('Identitas Brand')
            ->assertSee(route('merchant.organization.edit'), false);
    }

    public function test_owner_dashboard_aggregates_gross_revenue_across_branches_for_completed_tickets_only(): void
    {
        $branchTwo = Business::create([
            'user_id' => $this->merchant->id,
            'organization_id' => $this->business->organization_id,
            'category_id' => $this->business->category_id,
            'name' => 'Klinik Sehat Ubud',
            'slug' => 'klinik-sehat-ubud',
            'phone' => '081299998888',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_active' => true,
        ]);

        $branchTwoService = Service::create([
            'business_id' => $branchTwo->id,
            'name' => 'Konsultasi Spesialis',
            'duration_minutes' => 30,
            'price' => 125000,
            'is_active' => true,
        ]);

        $queueOne = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::now('Asia/Makassar')->toDateString(),
            'current_number' => 2,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        $queueTwo = DailyQueue::create([
            'business_id' => $branchTwo->id,
            'queue_date' => Carbon::now('Asia/Makassar')->toDateString(),
            'current_number' => 2,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        // Branch 1: 1 completed (50.000) + 1 cancelled (excluded)
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $queueOne->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Cabang 1 Selesai',
            'status' => QueueTicket::STATUS_SELESAI,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-rev-1',
            'completed_at' => now(),
        ]);

        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $queueOne->id,
            'service_id' => $this->service->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Pelanggan Cabang 1 Batal',
            'status' => QueueTicket::STATUS_DIBATALKAN,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-rev-2',
        ]);

        // Branch 2: 2 completed (250.000) + 1 waiting (excluded)
        QueueTicket::create([
            'business_id' => $branchTwo->id,
            'daily_queue_id' => $queueTwo->id,
            'service_id' => $branchTwoService->id,
            'ticket_number' => 'B-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan Cabang 2 Selesai 1',
            'status' => QueueTicket::STATUS_SELESAI,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-rev-3',
            'completed_at' => now(),
        ]);

        QueueTicket::create([
            'business_id' => $branchTwo->id,
            'daily_queue_id' => $queueTwo->id,
            'service_id' => $branchTwoService->id,
            'ticket_number' => 'B-002',
            'sequence_number' => 2,
            'customer_name' => 'Pelanggan Cabang 2 Selesai 2',
            'status' => QueueTicket::STATUS_SELESAI,
            'source' => QueueTicket::SOURCE_WALK_IN,
            'access_token' => 'tok-rev-4',
            'completed_at' => now(),
        ]);

        QueueTicket::create([
            'business_id' => $branchTwo->id,
            'daily_queue_id' => $queueTwo->id,
            'service_id' => $branchTwoService->id,
            'ticket_number' => 'B-003',
            'sequence_number' => 3,
            'customer_name' => 'Pelanggan Cabang 2 Menunggu',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'source' => QueueTicket::SOURCE_ONLINE,
            'access_token' => 'tok-rev-5',
        ]);

        $response = $this->actingAs($this->merchant)->get(route('merchant.dashboard', ['period' => 'today']));

        $response->assertOk()
            ->assertSee('Estimasi Pemasukan Kotor')
            ->assertSee('Rp 300.000')
            ->assertSee('Rp 50.000')
            ->assertSee('Rp 250.000');
    }
}

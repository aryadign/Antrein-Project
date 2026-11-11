<?php

namespace Tests\Feature\Admin;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_merchant_cannot_access_user_management(): void
    {
        $response = $this->get(route('admin.users.index'));
        $response->assertRedirect(route('login'));

        $merchant = User::factory()->merchant()->create();
        $response = $this->actingAs($merchant)->get(route('admin.users.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_users_list_and_filters(): void
    {
        $actingAdmin = User::factory()->admin()->create(['name' => 'Platform Auditor', 'email' => 'auditor@antrein.test']);
        User::factory()->admin()->create(['name' => 'Super Admin', 'email' => 'admin@antrein.test']);
        User::factory()->merchant()->create(['name' => 'Pak Joko', 'email' => 'joko@warung.test']);

        $response = $this->actingAs($actingAdmin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertViewIs('admin.users.index');
        $response->assertSee('Super Admin');
        $response->assertSee('Pak Joko');

        // Filter by role
        $roleResponse = $this->actingAs($actingAdmin)->get(route('admin.users.index', ['role' => 'admin']));
        $roleResponse->assertOk();
        $roleResponse->assertSee('Super Admin');
        $roleResponse->assertDontSee('Pak Joko');

        // Search by name
        $searchResponse = $this->actingAs($actingAdmin)->get(route('admin.users.index', ['search' => 'Joko']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Pak Joko');
        $searchResponse->assertDontSee('Super Admin');
    }

    public function test_admin_can_view_user_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = User::factory()->merchant()->create(['name' => 'Bu Siti']);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $merchant));

        $response->assertOk();
        $response->assertViewIs('admin.users.show');
        $response->assertSee('Bu Siti');
        $response->assertSee($merchant->email);
    }

    public function test_deactivating_merchant_with_active_queues_fails_without_emergency_force(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = User::factory()->merchant()->create();

        $category = BusinessCategory::create([
            'name' => 'Klinik',
            'slug' => 'klinik-' . uniqid(),
            'is_active' => true,
        ]);

        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Gigi Denpasar',
            'slug' => 'klinik-gigi-denpasar',
            'phone' => '081234567890',
            'address' => 'Jl. Teuku Umar No. 5',
            'timezone' => 'Asia/Makassar',
            'is_active' => true,
            'is_suspended' => false,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Pembersihan Karang Gigi',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $dailyQueue = DailyQueue::create([
            'business_id' => $business->id,
            'queue_date' => Carbon::today('Asia/Makassar')->toDateString(),
            'total_queue' => 1,
            'current_number' => 0,
            'is_open' => true,
        ]);

        $activeTicket = QueueTicket::create([
            'daily_queue_id' => $dailyQueue->id,
            'business_id' => $business->id,
            'service_id' => $service->id,
            'ticket_number' => 'G-001',
            'sequence_number' => 1,
            'customer_name' => 'Dewi Lestari',
            'customer_phone' => '081234567899',
            'source' => 'online',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'tok-gigi-001',
        ]);

        // Attempting deactivation without emergency_force should be rejected by interlock (OQ-03)
        $response = $this->actingAs($admin)->post(route('admin.users.deactivate', $merchant), [
            'reason' => 'Evaluasi berkala',
            'emergency_force' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $merchant->refresh();
        $this->assertTrue((bool) $merchant->is_active);
    }

    public function test_deactivating_merchant_with_emergency_force_succeeds_and_cancels_tickets(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = User::factory()->merchant()->create();

        $category = BusinessCategory::create([
            'name' => 'Klinik',
            'slug' => 'klinik-' . uniqid(),
            'is_active' => true,
        ]);

        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Gigi Denpasar 2',
            'slug' => 'klinik-gigi-denpasar-2',
            'phone' => '081234567890',
            'address' => 'Jl. Teuku Umar No. 5',
            'timezone' => 'Asia/Makassar',
            'is_active' => true,
            'is_suspended' => false,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Pembersihan Karang Gigi',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $dailyQueue = DailyQueue::create([
            'business_id' => $business->id,
            'queue_date' => Carbon::today('Asia/Makassar')->toDateString(),
            'total_queue' => 1,
            'current_number' => 0,
            'is_open' => true,
        ]);

        $activeTicket = QueueTicket::create([
            'daily_queue_id' => $dailyQueue->id,
            'business_id' => $business->id,
            'service_id' => $service->id,
            'ticket_number' => 'G-002',
            'sequence_number' => 1,
            'customer_name' => 'Dewi Lestari',
            'customer_phone' => '081234567899',
            'source' => 'online',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'tok-gigi-002',
        ]);

        // Attempting deactivation with emergency_force should succeed
        $response = $this->actingAs($admin)->post(route('admin.users.deactivate', $merchant), [
            'reason' => 'Penutupan darurat oleh admin',
            'emergency_force' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $merchant->refresh();
        $this->assertFalse((bool) $merchant->is_active);

        $activeTicket->refresh();
        $this->assertEquals(QueueTicket::STATUS_DIBATALKAN, $activeTicket->status);
    }

    public function test_last_active_admin_protection_prevents_deactivation(): void
    {
        // Only 1 admin in system
        $soleAdmin = User::factory()->admin()->create(['is_active' => true]);

        $response = $this->actingAs($soleAdmin)->post(route('admin.users.deactivate', $soleAdmin), [
            'reason' => 'Mau nonaktifkan akun sendiri',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $soleAdmin->refresh();
        $this->assertTrue((bool) $soleAdmin->is_active);
    }

    public function test_admin_can_activate_inactive_user(): void
    {
        $admin = User::factory()->admin()->create();
        $inactiveMerchant = User::factory()->merchant()->create(['is_active' => false]);

        $response = $this->actingAs($admin)->post(route('admin.users.activate', $inactiveMerchant));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $inactiveMerchant->refresh();
        $this->assertTrue((bool) $inactiveMerchant->is_active);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user_activated',
            'user_id' => $admin->id,
        ]);
    }
}


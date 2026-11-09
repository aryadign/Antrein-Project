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

class BusinessManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function createBusinessFixture(string $name = 'Barbershop Asli', bool $isSuspended = false): array
    {
        $category = BusinessCategory::create([
            'name' => 'Barbershop',
            'slug' => 'barbershop-' . uniqid(),
            'is_active' => true,
        ]);

        $owner = User::factory()->merchant()->create();

        $business = Business::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'name' => $name,
            'slug' => str($name)->slug()->value() . '-' . uniqid(),
            'phone' => '081234567890',
            'address' => 'Jl. Gatot Subroto No. 10',
            'timezone' => 'Asia/Makassar',
            'is_active' => true,
            'is_suspended' => $isSuspended,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Cukur Rambut',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        return [$business, $owner, $category, $service];
    }

    public function test_guest_and_merchant_cannot_access_business_management(): void
    {
        $response = $this->get(route('admin.businesses.index'));
        $response->assertRedirect(route('login'));

        $merchant = User::factory()->merchant()->create();
        $response = $this->actingAs($merchant)->get(route('admin.businesses.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_businesses_list(): void
    {
        $admin = User::factory()->admin()->create();
        [$business] = $this->createBusinessFixture('Salon Cantik Bali');

        $response = $this->actingAs($admin)->get(route('admin.businesses.index'));

        $response->assertOk();
        $response->assertViewIs('admin.businesses.index');
        $response->assertSee('Salon Cantik Bali');
    }

    public function test_admin_can_search_and_filter_businesses(): void
    {
        $admin = User::factory()->admin()->create();
        [$business1, $owner1, $cat1] = $this->createBusinessFixture('Apotek Sejahtera', false);
        [$business2, $owner2, $cat2] = $this->createBusinessFixture('Bengkel Jaya', true);

        // Search by name
        $response = $this->actingAs($admin)->get(route('admin.businesses.index', ['search' => 'Apotek']));
        $response->assertOk();
        $response->assertSee('Apotek Sejahtera');
        $response->assertDontSee('Bengkel Jaya');

        // Filter by suspension status
        $response = $this->actingAs($admin)->get(route('admin.businesses.index', ['status' => 'suspended']));
        $response->assertOk();
        $response->assertSee('Bengkel Jaya');
        $response->assertDontSee('Apotek Sejahtera');
    }

    public function test_admin_can_view_business_detail(): void
    {
        $admin = User::factory()->admin()->create();
        [$business, $owner, $category, $service] = $this->createBusinessFixture('Klinik Pratama');

        $response = $this->actingAs($admin)->get(route('admin.businesses.show', $business));

        $response->assertOk();
        $response->assertViewIs('admin.businesses.show');
        $response->assertSee('Klinik Pratama');
        $response->assertSee($owner->name);
        $response->assertSee($service->name);
    }

    public function test_admin_can_suspend_active_business(): void
    {
        $admin = User::factory()->admin()->create();
        [$business] = $this->createBusinessFixture('Kafe Santai', false);

        $response = $this->actingAs($admin)->patch(route('admin.businesses.toggle-suspension', $business), [
            'suspension_reason' => 'Pelanggaran ketentuan jam operasional.',
        ]);

        $response->assertRedirect();
        $business->refresh();
        $this->assertTrue($business->is_suspended);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'business_suspended',
            'business_id' => $business->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_unsuspend_suspended_business(): void
    {
        $admin = User::factory()->admin()->create();
        [$business] = $this->createBusinessFixture('Kafe Santai', true);

        $response = $this->actingAs($admin)->patch(route('admin.businesses.toggle-suspension', $business), [
            'suspension_reason' => 'Pemulihan akun setelah klarifikasi.',
        ]);

        $response->assertRedirect();
        $business->refresh();
        $this->assertFalse($business->is_suspended);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'business_reactivated',
            'business_id' => $business->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_suspended_business_blocks_new_tickets_while_existing_tickets_remain_serviceable(): void
    {
        [$business, $owner, $category, $service] = $this->createBusinessFixture('Optik Fokus', false);

        $today = Carbon::today('Asia/Makassar')->toDateString();
        $dailyQueue = DailyQueue::create([
            'business_id' => $business->id,
            'queue_date' => $today,
            'total_queue' => 1,
            'current_number' => 0,
            'is_open' => true,
        ]);

        $activeTicket = QueueTicket::create([
            'daily_queue_id' => $dailyQueue->id,
            'business_id' => $business->id,
            'service_id' => $service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Budi Susanto',
            'customer_phone' => '081299991111',
            'source' => 'online',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'tok-optik-001',
        ]);

        // Suspend the business
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.businesses.toggle-suspension', $business), [
            'suspension_reason' => 'Pemeriksaan kepatuhan.',
        ]);

        $business->refresh();
        $this->assertTrue($business->is_suspended);

        // Attempting to register new ticket online fails because business is suspended
        $joinResponse = $this->post(route('customer.queue.take', ['slug' => $business->slug]), [
            'service_id' => $service->id,
            'customer_name' => 'Calon Pelanggan Baru',
            'customer_phone' => '081233334444',
        ]);
        $joinResponse->assertRedirect();
        $joinResponse->assertSessionHasErrors();

        // OQ-07: Merchant can still service existing active ticket
        $callResponse = $this->actingAs($owner)->post(route('merchant.queues.call-next'));
        $callResponse->assertRedirect();

        $activeTicket->refresh();
        $this->assertEquals(QueueTicket::STATUS_DIPANGGIL, $activeTicket->status);
    }
}


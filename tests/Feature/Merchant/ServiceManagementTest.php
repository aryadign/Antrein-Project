<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;
    private Service $existingService;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Salon',
            'slug' => 'salon',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Salon Cantika',
            'slug' => 'salon-cantika',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->existingService = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Cuci & Blow',
            'description' => 'Cuci rambut bersih dan blow dry',
            'duration_minutes' => 30,
            'price' => 50000,
            'daily_quota' => 20,
            'is_active' => true,
        ]);
    }

    public function test_merchant_can_view_own_services(): void
    {
        $response = $this->actingAs($this->merchant)->get(route('merchant.services.index'));

        $response->assertOk();
        $response->assertSee('Cuci & Blow');
        $response->assertSee('50.000');
        $response->assertSee('20 tiket/hari');
    }

    public function test_merchant_can_create_new_service(): void
    {
        $response = $this->actingAs($this->merchant)->post(route('merchant.services.store'), [
            'name' => 'Creambath Tradisional',
            'description' => 'Perawatan rambut dengan ekstrak lidah buaya',
            'duration_minutes' => 60,
            'price' => 75000,
            'daily_quota' => 15,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('merchant.services.index'));

        $this->assertDatabaseHas('services', [
            'business_id' => $this->business->id,
            'name' => 'Creambath Tradisional',
            'duration_minutes' => 60,
            'price' => 75000,
            'daily_quota' => 15,
            'is_active' => true,
        ]);
    }

    public function test_negative_price_is_rejected(): void
    {
        $response = $this->actingAs($this->merchant)->post(route('merchant.services.store'), [
            'name' => 'Layanan Error',
            'duration_minutes' => 30,
            'price' => -10000,
        ]);

        $response->assertSessionHasErrors(['price']);
    }

    public function test_zero_or_negative_duration_is_rejected(): void
    {
        $response = $this->actingAs($this->merchant)->post(route('merchant.services.store'), [
            'name' => 'Layanan Durasi Nol',
            'duration_minutes' => 0,
            'price' => 20000,
        ]);

        $response->assertSessionHasErrors(['duration_minutes']);
    }

    public function test_invalid_quota_is_rejected(): void
    {
        $response = $this->actingAs($this->merchant)->post(route('merchant.services.store'), [
            'name' => 'Layanan Kuota Negatif',
            'duration_minutes' => 30,
            'price' => 20000,
            'daily_quota' => 0, // Must be >= 1
        ]);

        $response->assertSessionHasErrors(['daily_quota']);
    }

    public function test_merchant_can_edit_existing_service(): void
    {
        $response = $this->actingAs($this->merchant)->put(route('merchant.services.update', $this->existingService), [
            'name' => 'Cuci, Blow & Catok',
            'description' => 'Paket komplit penataan rambut',
            'duration_minutes' => 45,
            'price' => 70000,
            'daily_quota' => 25,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('merchant.services.index'));

        $this->assertDatabaseHas('services', [
            'id' => $this->existingService->id,
            'name' => 'Cuci, Blow & Catok',
            'duration_minutes' => 45,
            'price' => 70000,
        ]);
    }

    public function test_merchant_can_toggle_service_active_status(): void
    {
        $response = $this->actingAs($this->merchant)->patch(route('merchant.services.toggle-status', $this->existingService));

        $response->assertRedirect();
        $this->assertFalse($this->existingService->fresh()->is_active);

        // Toggle back to active
        $this->actingAs($this->merchant)->patch(route('merchant.services.toggle-status', $this->existingService));
        $this->assertTrue($this->existingService->fresh()->is_active);
    }

    public function test_soft_deletion_preserves_historical_tickets_and_foreign_keys(): void
    {
        $dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => now()->toDateString(),
            'current_number' => 1,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        $ticket = QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $this->existingService->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => Str::random(40),
            'customer_name' => 'Luh Made',
            'customer_phone' => '081234567899',
        ]);

        // Delete the service
        $response = $this->actingAs($this->merchant)->delete(route('merchant.services.destroy', $this->existingService));

        $response->assertRedirect(route('merchant.services.index'));

        // Service must be soft-deleted, not hard-deleted
        $this->assertSoftDeleted('services', ['id' => $this->existingService->id]);

        // Historical ticket still references the service and exists intact
        $freshTicket = $ticket->fresh();
        $this->assertNotNull($freshTicket);
        $this->assertSame($this->existingService->id, $freshTicket->service_id);
    }

    public function test_cross_tenant_service_modification_is_rejected(): void
    {
        $otherOwner = User::factory()->merchant()->create();
        $otherBusiness = Business::create([
            'user_id' => $otherOwner->id,
            'category_id' => $this->business->category_id,
            'name' => 'Salon Sebelah',
            'slug' => 'salon-sebelah',
            'phone' => '08199999999',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $otherService = Service::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Layanan Pihak Lain',
            'duration_minutes' => 30,
            'price' => 50000,
            'is_active' => true,
        ]);

        // Merchant tries to edit or delete Other Service
        $response = $this->actingAs($this->merchant)->put(route('merchant.services.update', $otherService), [
            'name' => 'Hack Service',
            'duration_minutes' => 10,
            'price' => 1000,
        ]);

        $response->assertForbidden();

        $delResponse = $this->actingAs($this->merchant)->delete(route('merchant.services.destroy', $otherService));
        $delResponse->assertForbidden();
    }

    public function test_quota_calculation_foundation_accurately_counts_quota_consuming_tickets(): void
    {
        $service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Potong Rambut Berkuota',
            'duration_minutes' => 30,
            'price' => 50000,
            'daily_quota' => 5,
            'is_active' => true,
        ]);

        $dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => now()->toDateString(),
            'current_number' => 3,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        // Ticket 1: menunggu (consumes quota)
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => Str::random(40),
            'customer_name' => 'Pelanggan 1',
        ]);

        // Ticket 2: selesai (consumes quota)
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $service->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_SELESAI,
            'access_token' => Str::random(40),
            'customer_name' => 'Pelanggan 2',
        ]);

        // Ticket 3: dibatalkan (RELEASES quota, does NOT consume)
        QueueTicket::create([
            'business_id' => $this->business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $service->id,
            'ticket_number' => 'A-003',
            'sequence_number' => 3,
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_DIBATALKAN,
            'access_token' => Str::random(40),
            'customer_name' => 'Pelanggan 3',
        ]);

        // 5 total quota - 2 consumed tickets = 3 remaining quota
        $remaining = $service->getRemainingQuotaForDailyQueue($dailyQueue);
        $this->assertSame(3, $remaining);
    }
}

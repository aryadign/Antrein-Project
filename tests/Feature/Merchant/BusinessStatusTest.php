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

class BusinessStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Klinik',
            'slug' => 'klinik',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Kasih Ibu',
            'slug' => 'klinik-kasih-ibu',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Pemeriksaan Dokter',
            'duration_minutes' => 20,
            'price' => 50000,
            'is_active' => true,
        ]);
    }

    public function test_merchant_can_change_status_from_buka_to_jeda(): void
    {
        $response = $this->actingAs($this->merchant)->patch(route('merchant.settings.status'), [
            'status' => Business::STATUS_JEDA,
        ]);

        $response->assertRedirect(route('merchant.settings', ['tab' => 'status']));
        $this->assertSame(Business::STATUS_JEDA, $this->business->fresh()->status);
        $this->assertTrue($this->business->fresh()->isPaused());
    }

    public function test_merchant_can_change_status_from_jeda_to_tutup(): void
    {
        $this->business->update(['status' => Business::STATUS_JEDA]);

        $response = $this->actingAs($this->merchant)->patch(route('merchant.settings.status'), [
            'status' => Business::STATUS_TUTUP,
        ]);

        $response->assertRedirect(route('merchant.settings', ['tab' => 'status']));
        $this->assertSame(Business::STATUS_TUTUP, $this->business->fresh()->status);
        $this->assertTrue($this->business->fresh()->isClosed());
    }

    public function test_merchant_can_resume_operations_to_buka(): void
    {
        $this->business->update(['status' => Business::STATUS_TUTUP]);

        $response = $this->actingAs($this->merchant)->patch(route('merchant.settings.status'), [
            'status' => Business::STATUS_BUKA,
        ]);

        $response->assertRedirect();
        $this->assertSame(Business::STATUS_BUKA, $this->business->fresh()->status);
        $this->assertTrue($this->business->fresh()->isOpen());
    }

    public function test_suspended_business_blocks_manual_status_change(): void
    {
        $this->business->update([
            'status' => Business::STATUS_BUKA,
            'is_suspended' => true,
        ]);

        $response = $this->actingAs($this->merchant)->patch(route('merchant.settings.status'), [
            'status' => Business::STATUS_TUTUP,
        ]);

        $response->assertSessionHasErrors(['status']);
        $this->assertSame(Business::STATUS_BUKA, $this->business->fresh()->status);
    }

    public function test_changing_status_does_not_delete_or_alter_existing_active_tickets(): void
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
            'service_id' => $this->service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => Str::random(40),
            'customer_name' => 'Wayan Sudira',
            'customer_phone' => '081234567899',
        ]);

        // Change operational status to jeda, then tutup
        $this->actingAs($this->merchant)->patch(route('merchant.settings.status'), [
            'status' => Business::STATUS_JEDA,
        ]);

        $this->actingAs($this->merchant)->patch(route('merchant.settings.status'), [
            'status' => Business::STATUS_TUTUP,
        ]);

        // Active ticket MUST remain intact with status 'menunggu'
        $freshTicket = $ticket->fresh();
        $this->assertNotNull($freshTicket);
        $this->assertSame(QueueTicket::STATUS_MENUNGGU, $freshTicket->status);
    }
}


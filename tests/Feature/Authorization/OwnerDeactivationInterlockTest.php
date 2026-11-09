<?php

namespace Tests\Feature\Authorization;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueStatusHistory;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\UserDeactivationService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OwnerDeactivationInterlockTest extends TestCase
{
    use RefreshDatabase;

    private function createBusinessWithActiveTicket(string $ticketStatus): array
    {
        $category = BusinessCategory::create([
            'name' => 'Bengkel',
            'slug' => 'bengkel',
            'is_active' => true,
        ]);

        $owner = User::factory()->merchant()->create();

        $business = Business::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Bengkel Maju',
            'slug' => 'bengkel-maju',
            'address' => 'Jl. Otomotif No. 5',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Ganti Oli',
            'duration_minutes' => 20,
            'price' => 75000,
            'is_active' => true,
        ]);

        $dailyQueue = DailyQueue::create([
            'business_id' => $business->id,
            'queue_date' => now()->toDateString(),
            'current_number' => 1,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        $ticket = QueueTicket::create([
            'business_id' => $business->id,
            'daily_queue_id' => $dailyQueue->id,
            'service_id' => $service->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => $ticketStatus,
            'access_token' => Str::random(40),
            'customer_name' => 'Pelanggan Uji',
            'customer_phone' => '081299998888',
        ]);

        return [$owner, $business, $ticket];
    }

    public function test_owner_without_active_tickets_can_be_deactivated_normally(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->merchant()->create();

        $service = app(UserDeactivationService::class);
        $result = $service->deactivate(
            targetUser: $owner,
            performedBy: $admin,
            emergencyForce: false
        );

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['cancelled_tickets_count']);
        $this->assertFalse($owner->fresh()->is_active);
    }

    public function test_normal_deactivation_is_blocked_when_active_tickets_exist(): void
    {
        $admin = User::factory()->admin()->create();
        [$owner, $business, $ticket] = $this->createBusinessWithActiveTicket(QueueTicket::STATUS_MENUNGGU);

        $service = app(UserDeactivationService::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Akun pemilik tidak dapat dinonaktifkan karena masih terdapat 1 tiket antrean aktif');

        $service->deactivate(
            targetUser: $owner,
            performedBy: $admin,
            emergencyForce: false
        );
    }

    public function test_emergency_force_deactivation_cancels_active_tickets_and_deactivates_owner(): void
    {
        $admin = User::factory()->admin()->create();
        [$owner, $business, $ticket] = $this->createBusinessWithActiveTicket(QueueTicket::STATUS_DIPANGGIL);

        $service = app(UserDeactivationService::class);
        $result = $service->deactivate(
            targetUser: $owner,
            performedBy: $admin,
            emergencyForce: true,
            reason: 'Penutupan darurat operasional oleh dinas terkait'
        );

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['cancelled_tickets_count']);
        $this->assertFalse($owner->fresh()->is_active);

        // Verify ticket was cancelled with audit reason
        $freshTicket = $ticket->fresh();
        $this->assertSame(QueueTicket::STATUS_DIBATALKAN, $freshTicket->status);
        $this->assertNotNull($freshTicket->cancelled_at);
        $this->assertStringContainsString('Penonaktifan akun pemilik oleh administrator (Darurat)', $freshTicket->cancellation_reason);

        // Verify status history
        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $ticket->id,
            'event_type' => QueueStatusHistory::EVENT_TICKET_CANCELLED,
            'actor_type' => QueueStatusHistory::ACTOR_SYSTEM,
            'actor_id' => $admin->id,
        ]);

        // Verify activity log
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'business_id' => $business->id,
            'action' => 'emergency_deactivation_force',
        ]);
    }

    public function test_emergency_force_by_non_admin_is_strictly_forbidden(): void
    {
        $nonAdmin = User::factory()->merchant()->create();
        [$owner, $business, $ticket] = $this->createBusinessWithActiveTicket(QueueTicket::STATUS_SEDANG_DILAYANI);

        $service = app(UserDeactivationService::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('hanya boleh dijalankan oleh Administrator platform');

        $service->deactivate(
            targetUser: $owner,
            performedBy: $nonAdmin,
            emergencyForce: true
        );
    }

    public function test_admin_deactivation_endpoint_handles_interlock_via_http(): void
    {
        $admin = User::factory()->admin()->create();
        [$owner, $business, $ticket] = $this->createBusinessWithActiveTicket(QueueTicket::STATUS_MENUNGGU);

        // Normal deactivation attempt via HTTP fails with validation/error
        $response = $this->actingAs($admin)
            ->postJson(route('admin.users.deactivate', $owner), [
                'emergency_force' => false,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertTrue($owner->fresh()->is_active);

        // Emergency force via HTTP succeeds
        $forceResponse = $this->actingAs($admin)
            ->postJson(route('admin.users.deactivate', $owner), [
                'emergency_force' => true,
                'reason' => 'Emergency shutdown',
            ]);

        $forceResponse->assertOk();
        $forceResponse->assertJson([
            'success' => true,
            'cancelled_tickets_count' => 1,
        ]);
        $this->assertFalse($owner->fresh()->is_active);
    }
}

<?php

namespace Tests\Feature\Audit;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueStatusHistory;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_suspension_and_reactivation_audit_logging(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = User::factory()->merchant()->create();

        $category = BusinessCategory::create([
            'name' => 'Kategori Audit',
            'slug' => 'kategori-audit',
            'is_active' => true,
        ]);

        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Kafe Audit',
            'slug' => 'kafe-audit',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'is_active' => true,
            'is_suspended' => false,
        ]);

        // 1. Suspend
        $this->actingAs($admin)->patch(route('admin.businesses.toggle-suspension', $business), [
            'suspension_reason' => 'Pelanggaran ketentuan operasional.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'business_suspended',
            'business_id' => $business->id,
            'user_id' => $admin->id,
        ]);

        // 2. Reactivate
        $this->actingAs($admin)->patch(route('admin.businesses.toggle-suspension', $business), [
            'suspension_reason' => 'Pemulihan akun setelah klarifikasi.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'business_reactivated',
            'business_id' => $business->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_merchant_operational_status_change_audit_logging(): void
    {
        $category = BusinessCategory::create([
            'name' => 'Kategori Status',
            'slug' => 'kategori-status',
            'is_active' => true,
        ]);

        $merchant = User::factory()->merchant()->create();
        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Bengkel Status',
            'slug' => 'bengkel-status',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_active' => true,
        ]);

        Service::create([
            'business_id' => $business->id,
            'name' => 'Layanan Service',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($merchant)->patch(route('merchant.settings.status'), [
            'status' => Business::STATUS_JEDA,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'operational_status_changed',
            'business_id' => $business->id,
            'user_id' => $merchant->id,
        ]);
    }

    public function test_user_deactivation_and_activation_audit_logging(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->merchant()->create();

        // 1. Deactivate
        $this->actingAs($admin)->post(route('admin.users.deactivate', $targetUser), [
            'reason' => 'Pemeriksaan kepatuhan akun',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user_deactivated',
            'user_id' => $admin->id,
        ]);

        // 2. Activate
        $this->actingAs($admin)->post(route('admin.users.activate', $targetUser));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user_activated',
            'user_id' => $admin->id,
        ]);
    }

    public function test_business_category_lifecycle_audit_logging(): void
    {
        $admin = User::factory()->admin()->create();

        // Create
        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Kategori Log Test',
            'description' => 'Kategori untuk uji audit log',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'category_created',
            'user_id' => $admin->id,
        ]);

        $category = BusinessCategory::where('name', 'Kategori Log Test')->firstOrFail();

        // Update
        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Kategori Log Updated',
            'slug' => 'kategori-log-updated',
            'description' => 'Deskripsi baru',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'category_updated',
            'user_id' => $admin->id,
        ]);

        // Toggle Status
        $this->actingAs($admin)->patch(route('admin.categories.toggle-status', $category));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'category_status_toggled',
            'user_id' => $admin->id,
        ]);
    }

    public function test_queue_status_histories_record_lifecycle_transitions(): void
    {
        $merchant = User::factory()->merchant()->create();
        $category = BusinessCategory::create([
            'name' => 'Klinik Gigi',
            'slug' => 'klinik-gigi-trans',
            'is_active' => true,
        ]);

        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Gigi Denpasar',
            'slug' => 'klinik-gigi-denpasar',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_active' => true,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Pembersihan Karang Gigi',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        /** @var QueueService $queueService */
        $queueService = app(QueueService::class);

        // 1. Walk-in ticket creation
        $ticket = $queueService->generateWalkInTicket(
            business: $business,
            service: $service,
            customerName: 'Pasien Uji',
            operator: $merchant
        );

        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $ticket->id,
            'to_status' => QueueTicket::STATUS_MENUNGGU,
            'actor_type' => QueueStatusHistory::ACTOR_MERCHANT,
            'actor_id' => $merchant->id,
        ]);

        // 2. Call next ticket
        $queueService->callNext($business, $merchant);
        $ticket->refresh();

        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $ticket->id,
            'from_status' => QueueTicket::STATUS_MENUNGGU,
            'to_status' => QueueTicket::STATUS_DIPANGGIL,
        ]);

        // 3. Start service
        $queueService->startService($ticket, $merchant);

        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $ticket->id,
            'from_status' => QueueTicket::STATUS_DIPANGGIL,
            'to_status' => QueueTicket::STATUS_SEDANG_DILAYANI,
        ]);

        // 4. Complete ticket
        $queueService->completeTicket($ticket, $merchant);

        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $ticket->id,
            'from_status' => QueueTicket::STATUS_SEDANG_DILAYANI,
            'to_status' => QueueTicket::STATUS_SELESAI,
        ]);
    }

    public function test_sensitive_credentials_and_tokens_are_stripped_from_audit_logs(): void
    {
        /** @var AuditLogService $auditService */
        $auditService = app(AuditLogService::class);
        $user = User::factory()->create();

        $log = $auditService->log(
            action: 'security_test',
            description: 'Uji penyaringan data sensitif audit log',
            user: $user,
            properties: [
                'user_name' => 'John Doe',
                'password' => 'secret_password_123',
                'password_confirmation' => 'secret_password_123',
                'access_token' => 'sensitive_token_abc_xyz',
                'credit_card' => '4111222233334444',
                'customer_phone' => '081234567890',
            ]
        );

        $properties = $log->properties;

        // Verify credentials stripped
        $this->assertArrayNotHasKey('password', $properties);
        $this->assertArrayNotHasKey('password_confirmation', $properties);
        $this->assertArrayNotHasKey('access_token', $properties);
        $this->assertArrayNotHasKey('credit_card', $properties);

        // Verify safe fields retained
        $this->assertEquals('John Doe', $properties['user_name']);

        // Verify phone number masked (OQ-14)
        $this->assertEquals('0812****7890', $properties['customer_phone']);
    }
}

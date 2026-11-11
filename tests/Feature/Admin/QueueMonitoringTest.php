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

class QueueMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function createTicketFixture(): array
    {
        $category = BusinessCategory::create([
            'name' => 'Klinik Medis',
            'slug' => 'klinik-medis-' . uniqid(),
            'is_active' => true,
        ]);

        $merchant = User::factory()->merchant()->create();
        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Klinik Bali Husada',
            'slug' => 'klinik-bali-husada-' . uniqid(),
            'phone' => '081234567890',
            'address' => 'Jl. Diponegoro No. 12',
            'timezone' => 'Asia/Makassar',
            'is_active' => true,
            'is_suspended' => false,
        ]);

        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Pemeriksaan Umum',
            'duration_minutes' => 20,
            'is_active' => true,
        ]);

        $dailyQueue = DailyQueue::create([
            'business_id' => $business->id,
            'queue_date' => Carbon::today('Asia/Makassar')->toDateString(),
            'total_queue' => 1,
            'current_number' => 0,
            'is_open' => true,
        ]);

        $ticket = QueueTicket::create([
            'daily_queue_id' => $dailyQueue->id,
            'business_id' => $business->id,
            'service_id' => $service->id,
            'ticket_number' => 'B-001',
            'sequence_number' => 1,
            'customer_name' => 'Made Suardana',
            'customer_phone' => '081234567890',
            'source' => 'online',
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'secret-customer-token-9988',
        ]);

        QueueStatusHistory::create([
            'queue_ticket_id' => $ticket->id,
            'event_type' => QueueStatusHistory::EVENT_STATUS_CHANGED,
            'from_status' => null,
            'to_status' => QueueTicket::STATUS_MENUNGGU,
            'actor_type' => QueueStatusHistory::ACTOR_CUSTOMER,
            'actor_id' => null,
            'note' => 'Pendaftaran antrean online',
            'created_at' => now(),
        ]);

        return [$ticket, $business, $service, $dailyQueue];
    }

    public function test_guest_and_merchant_cannot_access_queue_monitoring(): void
    {
        $response = $this->get(route('admin.queues.index'));
        $response->assertRedirect(route('login'));

        $merchant = User::factory()->merchant()->create();
        $response = $this->actingAs($merchant)->get(route('admin.queues.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_cross_tenant_queue_list(): void
    {
        $admin = User::factory()->admin()->create();
        [$ticket] = $this->createTicketFixture();

        $response = $this->actingAs($admin)->get(route('admin.queues.index'));

        $response->assertOk();
        $response->assertViewIs('admin.queues.index');
        $response->assertSee('B-001');
        $response->assertSee('Made Suardana');
        $response->assertSee('Klinik Bali Husada');
    }

    public function test_admin_can_filter_and_search_queues(): void
    {
        $admin = User::factory()->admin()->create();
        [$ticket] = $this->createTicketFixture();

        // Search by ticket number
        $response = $this->actingAs($admin)->get(route('admin.queues.index', ['search' => 'B-001']));
        $response->assertOk();
        $response->assertSee('B-001');

        // Filter by status
        $statusResponse = $this->actingAs($admin)->get(route('admin.queues.index', ['status' => 'menunggu']));
        $statusResponse->assertOk();
        $statusResponse->assertSee('B-001');

        $emptyStatusResponse = $this->actingAs($admin)->get(route('admin.queues.index', ['status' => 'selesai']));
        $emptyStatusResponse->assertOk();
        $emptyStatusResponse->assertDontSee('B-001');
    }

    public function test_customer_phone_is_masked_in_queue_listing_and_detail(): void
    {
        $admin = User::factory()->admin()->create();
        [$ticket] = $this->createTicketFixture();

        // Customer real phone is 081234567890 -> Masked: 0812****7890
        $listResponse = $this->actingAs($admin)->get(route('admin.queues.index'));
        $listResponse->assertOk();
        $listResponse->assertSee('0812****7890');
        $listResponse->assertDontSee('081234567890');

        $detailResponse = $this->actingAs($admin)->get(route('admin.queues.show', $ticket));
        $detailResponse->assertOk();
        $detailResponse->assertSee('0812****7890');
        $detailResponse->assertDontSee('081234567890');
    }

    public function test_customer_access_token_is_not_exposed(): void
    {
        $admin = User::factory()->admin()->create();
        [$ticket] = $this->createTicketFixture();

        $detailResponse = $this->actingAs($admin)->get(route('admin.queues.show', $ticket));
        $detailResponse->assertOk();
        $detailResponse->assertDontSee('secret-customer-token-9988');
    }

    public function test_queue_monitoring_view_is_strictly_read_only_with_no_mutation_buttons(): void
    {
        $admin = User::factory()->admin()->create();
        [$ticket] = $this->createTicketFixture();

        $detailResponse = $this->actingAs($admin)->get(route('admin.queues.show', $ticket));
        $detailResponse->assertOk();

        // Verify there are NO form actions attempting to call merchant operational actions
        $detailResponse->assertDontSee(route('merchant.queues.call-next'));
        $detailResponse->assertDontSee(route('merchant.queues.start-service', $ticket));
        $detailResponse->assertDontSee(route('merchant.queues.complete', $ticket));
        $detailResponse->assertDontSee(route('merchant.queues.skip', $ticket));
        $detailResponse->assertDontSee(route('merchant.queues.cancel', $ticket));

        $detailResponse->assertSee('Mode Audit Read-Only');
    }
}


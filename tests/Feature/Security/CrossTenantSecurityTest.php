<?php

namespace Tests\Feature\Security;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossTenantSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $merchantA;
    private Business $businessA;
    private Service $serviceA;
    private DailyQueue $queueA;
    private QueueTicket $ticketA;

    private User $merchantB;
    private Business $businessB;
    private Service $serviceB;
    private DailyQueue $queueB;
    private QueueTicket $ticketB;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'F&B',
            'slug' => 'f-and-b',
            'is_active' => true,
        ]);

        // Merchant A
        $this->merchantA = User::factory()->merchant()->create();
        $this->businessA = Business::create([
            'user_id' => $this->merchantA->id,
            'category_id' => $category->id,
            'name' => 'Kedai Kopi A',
            'slug' => 'kedai-kopi-a',
            'phone' => '081111111111',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);
        $this->serviceA = Service::create([
            'business_id' => $this->businessA->id,
            'name' => 'Espresso',
            'duration_minutes' => 10,
            'is_active' => true,
        ]);
        $this->queueA = DailyQueue::create([
            'business_id' => $this->businessA->id,
            'queue_date' => Carbon::today('Asia/Makassar'),
            'current_number' => 1,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);
        $this->ticketA = QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->queueA->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan A',
            'customer_phone' => '081234567890',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_WAITING,
            'access_token' => bin2hex(random_bytes(32)),
        ]);

        // Merchant B
        $this->merchantB = User::factory()->merchant()->create();
        $this->businessB = Business::create([
            'user_id' => $this->merchantB->id,
            'category_id' => $category->id,
            'name' => 'Kedai Kopi B',
            'slug' => 'kedai-kopi-b',
            'phone' => '082222222222',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);
        $this->serviceB = Service::create([
            'business_id' => $this->businessB->id,
            'name' => 'Manual Brew',
            'duration_minutes' => 15,
            'is_active' => true,
        ]);
        $this->queueB = DailyQueue::create([
            'business_id' => $this->businessB->id,
            'queue_date' => Carbon::today('Asia/Makassar'),
            'current_number' => 1,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);
        $this->ticketB = QueueTicket::create([
            'business_id' => $this->businessB->id,
            'daily_queue_id' => $this->queueB->id,
            'service_id' => $this->serviceB->id,
            'ticket_number' => 'B-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan B',
            'customer_phone' => '089876543210',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_WAITING,
            'access_token' => bin2hex(random_bytes(32)),
        ]);

        // Admin
        $this->admin = User::factory()->admin()->create();
    }

    public function test_merchant_a_cannot_view_or_modify_merchant_b_services(): void
    {
        $updateResponse = $this->actingAs($this->merchantA)->put(route('merchant.services.update', $this->serviceB), [
            'name' => 'Hacked Service Name',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        $updateResponse->assertStatus(403);
        $this->assertDatabaseMissing('services', ['name' => 'Hacked Service Name']);

        $deleteResponse = $this->actingAs($this->merchantA)->delete(route('merchant.services.destroy', $this->serviceB));
        $deleteResponse->assertStatus(403);
        $this->assertDatabaseHas('services', ['id' => $this->serviceB->id]);
    }

    public function test_merchant_a_cannot_call_or_operate_merchant_b_ticket(): void
    {
        // Merchant A attempts to operate on ticket belonging to Merchant B
        $response = $this->actingAs($this->merchantA)->post(route('merchant.queues.complete', $this->ticketB));
        $response->assertStatus(403);
        $this->assertEquals(QueueTicket::STATUS_WAITING, $this->ticketB->fresh()->status);

        $cancelResponse = $this->actingAs($this->merchantA)->post(route('merchant.queues.cancel', $this->ticketB));
        $cancelResponse->assertStatus(403);
        $this->assertEquals(QueueTicket::STATUS_WAITING, $this->ticketB->fresh()->status);
    }

    public function test_merchant_a_cannot_access_merchant_b_reports(): void
    {
        // Reports are strictly scoped to authenticated user's business
        $response = $this->actingAs($this->merchantA)->get(route('merchant.reports'));
        $response->assertOk();
        $response->assertSee('Espresso');
        $response->assertDontSee('Manual Brew');
    }

    public function test_merchant_cannot_access_admin_dashboard_or_settings(): void
    {
        $this->actingAs($this->merchantA)->get(route('admin.dashboard'))->assertStatus(403);
        $this->actingAs($this->merchantA)->get(route('admin.users.index'))->assertStatus(403);
        $this->actingAs($this->merchantA)->get(route('admin.businesses.index'))->assertStatus(403);
        $this->actingAs($this->merchantA)->get(route('admin.settings'))->assertStatus(403);
        $this->actingAs($this->merchantA)->get(route('admin.queues.index'))->assertStatus(403);
    }

    public function test_admin_cannot_execute_merchant_operational_queue_commands(): void
    {
        // Merchant routes require role:merchant
        $response = $this->actingAs($this->admin)->post(route('merchant.queues.call-next'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->admin)->post(route('merchant.queues.complete', $this->ticketA));
        $response->assertStatus(403);
    }
}

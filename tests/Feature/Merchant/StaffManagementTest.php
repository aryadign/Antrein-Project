<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\BusinessMembership;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private Business $businessA;

    private Service $serviceA;

    private DailyQueue $queueA;

    private User $ownerB;

    private Business $businessB;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Kecantikan & Barbershop',
            'slug' => 'kecantikan-barbershop',
            'is_active' => true,
        ]);

        // Business A & Owner A
        $this->ownerA = User::factory()->merchant()->create(['name' => 'Owner AntreIn A', 'email' => 'owner.a@example.com']);
        $this->businessA = Business::create([
            'user_id' => $this->ownerA->id,
            'category_id' => $category->id,
            'name' => 'Barbershop Prima A',
            'slug' => 'barbershop-prima-a',
            'phone' => '081234567891',
            'timezone' => 'Asia/Jakarta',
            'status' => Business::STATUS_BUKA,
        ]);

        $this->serviceA = Service::create([
            'business_id' => $this->businessA->id,
            'name' => 'Potong Rambut Dewasa',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->queueA = DailyQueue::create([
            'business_id' => $this->businessA->id,
            'queue_date' => now()->toDateString(),
            'current_number' => 0,
            'status' => 'active',
        ]);

        // Business B & Owner B
        $this->ownerB = User::factory()->merchant()->create(['name' => 'Owner AntreIn B', 'email' => 'owner.b@example.com']);
        $this->businessB = Business::create([
            'user_id' => $this->ownerB->id,
            'category_id' => $category->id,
            'name' => 'Klinik Sehat B',
            'slug' => 'klinik-sehat-b',
            'phone' => '081234567892',
            'timezone' => 'Asia/Jakarta',
            'status' => Business::STATUS_BUKA,
        ]);

        // Platform Admin
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Test P1-01: Auto-membership creation and model relations.
     */
    public function test_business_owner_automatically_gets_owner_membership(): void
    {
        $membership = BusinessMembership::where('user_id', $this->ownerA->id)->first();

        $this->assertNotNull($membership);
        $this->assertEquals($this->businessA->id, $membership->business_id);
        $this->assertEquals(BusinessMembership::ROLE_OWNER, $membership->role);
        $this->assertTrue($membership->is_active);

        $this->assertTrue($this->ownerA->isOwner());
        $this->assertFalse($this->ownerA->isOperator());
        $this->assertEquals($this->businessA->id, $this->ownerA->business->id);
        $this->assertTrue($this->businessA->isOwner($this->ownerA));
        $this->assertTrue($this->businessA->isMember($this->ownerA));
    }

    /**
     * Test P1-01 / M19: Duplicate business membership per user DB constraint.
     */
    public function test_user_cannot_belong_to_more_than_one_business(): void
    {
        $operator = User::factory()->merchant()->create(['name' => 'Staf Tunggal']);

        BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $this->expectException(QueryException::class);

        // Attempting to assign operator to same business A must fail unique constraint on (business_id, user_id)
        BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);
    }

    /**
     * Test P1-01 & P1-02: Owner can access staff management dashboard.
     */
    public function test_owner_can_view_staff_management_dashboard(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('merchant.staff.index'));

        $response->assertStatus(200);
        $response->assertSee('Kelola Staf &amp; Akun Operator', false);
        $response->assertSee($this->ownerA->email);
        $response->assertSee('Pemilik');
        $response->assertSee('Tambah Akun Staf');
        $response->assertDontSee('Undang Staf Baru');
    }

    /**
     * Test P1-02: Operator cannot access staff management dashboard.
     */
    public function test_operator_cannot_view_staff_management_dashboard(): void
    {
        $operator = User::factory()->merchant()->create();
        BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($operator)->get(route('merchant.staff.index'));

        $response->assertStatus(403);
    }

    /**
     * Test P1-02: Operator is blocked from owner-only routes (Reports, Exports, Settings, Services).
     */
    public function test_operator_is_forbidden_from_administrative_and_reporting_routes(): void
    {
        $operator = User::factory()->merchant()->create();
        BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $this->actingAs($operator)->get(route('merchant.reports'))->assertStatus(403);
        $this->actingAs($operator)->get(route('merchant.reports.export.csv'))->assertStatus(403);
        $this->actingAs($operator)->get(route('merchant.reports.export.pdf'))->assertStatus(403);
        $this->actingAs($operator)->get(route('merchant.settings'))->assertStatus(403);
        $this->actingAs($operator)->get(route('merchant.services.index'))->assertStatus(403);
    }

    /**
     * Test P1-02: Operator can access operational queue console and dashboard.
     */
    public function test_operator_can_access_dashboard_and_queues(): void
    {
        $operator = User::factory()->merchant()->create();
        BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $this->actingAs($operator)->get(route('merchant.dashboard'))->assertStatus(200);
        $this->actingAs($operator)->get(route('merchant.queues'))->assertStatus(200);
    }

    /**
     * Test P1-02: Operator can perform queue lifecycle (walk-in, call-next, recall, start-service, complete).
     */
    public function test_operator_can_execute_queue_lifecycle(): void
    {
        $operator = User::factory()->merchant()->create();
        BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        // 1. Create walk-in ticket
        $walkInResponse = $this->actingAs($operator)->post(route('merchant.queues.walk-in'), [
            'customer_name' => 'Pelanggan Walk-in',
            'customer_phone' => '081234567890',
            'service_id' => $this->serviceA->id,
        ]);
        $walkInResponse->assertRedirect();

        $ticket = QueueTicket::where('business_id', $this->businessA->id)->first();
        $this->assertNotNull($ticket);
        $this->assertEquals(QueueTicket::STATUS_WAITING, $ticket->status);

        // 2. Call next ticket
        $callResponse = $this->actingAs($operator)->post(route('merchant.queues.call-next'));
        $callResponse->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(QueueTicket::STATUS_CALLED, $ticket->status);
        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $ticket->id,
            'to_status' => QueueTicket::STATUS_CALLED,
            'actor_id' => $operator->id,
        ]);

        // 3. Recall ticket
        $recallResponse = $this->actingAs($operator)->post(route('merchant.queues.recall', $ticket));
        $recallResponse->assertRedirect();

        // 4. Start service
        $startResponse = $this->actingAs($operator)->post(route('merchant.queues.start-service', $ticket));
        $startResponse->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(QueueTicket::STATUS_SERVING, $ticket->status);

        // 5. Complete ticket
        $completeResponse = $this->actingAs($operator)->post(route('merchant.queues.complete', $ticket));
        $completeResponse->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(QueueTicket::STATUS_COMPLETED, $ticket->status);
    }

    /**
     * Test Operator-only queue operations: Owner cannot cancel ticket; only Operator can operate/cancel.
     */
    public function test_owner_cannot_cancel_ticket_but_operator_can(): void
    {
        $operator = User::factory()->merchant()->create();
        BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $ticket = QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->queueA->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_WAITING,
            'access_token' => 'tok12345678901234567890123456789012',
        ]);

        // Owner attempts to cancel -> 403 Forbidden
        $ownerResponse = $this->actingAs($this->ownerA)->post(route('merchant.queues.cancel', $ticket), [
            'reason' => 'Pembatalan oleh pemilik',
        ]);
        $ownerResponse->assertStatus(403);
        $ticket->refresh();
        $this->assertEquals(QueueTicket::STATUS_WAITING, $ticket->status);

        // Operator cancels -> Success
        $response = $this->actingAs($operator)->post(route('merchant.queues.cancel', $ticket), [
            'reason' => 'Alasan operasional loket',
        ]);
        $response->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(QueueTicket::STATUS_CANCELLED, $ticket->status);
    }

    /**
     * Test Core Engine Rule: Single active ticket holds across multiple operators.
     */
    public function test_single_active_ticket_rule_holds_across_multiple_operators(): void
    {
        $operator1 = User::factory()->merchant()->create();
        BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator1->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $operator2 = User::factory()->merchant()->create();
        BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator2->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->queueA->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Antrean 1',
            'customer_phone' => '081234567890',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_WAITING,
            'access_token' => 'tok11111111111111111111111111111111',
        ]);

        QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->queueA->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Antrean 2',
            'customer_phone' => '081234567890',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_WAITING,
            'access_token' => 'tok22222222222222222222222222222222',
        ]);

        // Operator 1 calls next -> Ticket A-001 becomes CALLED
        $this->actingAs($operator1)->post(route('merchant.queues.call-next'))->assertRedirect();

        // Operator 2 attempts to call next while A-001 is active -> Blocked with error
        $response = $this->actingAs($operator2)->post(route('merchant.queues.call-next'));
        $response->assertSessionHasErrors(['queue_error']);
    }

    /**
     * Test P1-01: Owner can directly create an operator staff account with encrypted password reference.
     */
    public function test_owner_can_create_operator_staff_directly_with_encrypted_password(): void
    {
        $response = $this->actingAs($this->ownerA)->post(route('merchant.staff.store'), [
            'name' => 'Kadek Operator',
            'email' => 'kadek.op@example.com',
            'password' => 'Rahasia123!',
            'business_id' => $this->businessA->id,
        ]);

        $response->assertRedirect(route('merchant.staff.index'));
        $response->assertSessionHas('status');

        $staffUser = User::where('email', 'kadek.op@example.com')->first();
        $this->assertNotNull($staffUser);
        $this->assertTrue(Hash::check('Rahasia123!', $staffUser->password));

        $membership = BusinessMembership::where('user_id', $staffUser->id)->first();
        $this->assertNotNull($membership);
        $this->assertEquals($this->businessA->id, $membership->business_id);
        $this->assertEquals(BusinessMembership::ROLE_OPERATOR, $membership->role);
        $this->assertTrue($membership->is_active);
        $this->assertSame('Rahasia123!', $membership->getDecryptedStaffPassword());

        $this->assertDatabaseHas('activity_logs', [
            'business_id' => $this->businessA->id,
            'action' => 'staff_account_created',
        ]);

        // View shows encrypted password for owner reveal toggle
        $this->actingAs($this->ownerA)->get(route('merchant.staff.index'))
            ->assertOk()
            ->assertSee('Kadek Operator')
            ->assertSee('kadek.op@example.com')
            ->assertSee('Rahasia123!');
    }

    /**
     * Test P1-01: Direct staff creation validates duplicate emails, short passwords, and cross-tenant branches.
     */
    public function test_direct_staff_creation_validation_rules(): void
    {
        // 1. Duplicate email (owner email)
        $this->actingAs($this->ownerA)->post(route('merchant.staff.store'), [
            'name' => 'Duplicate Owner',
            'email' => $this->ownerA->email,
            'password' => 'Antrein123!',
        ])->assertSessionHasErrors('email');

        // 2. Existing user email
        User::factory()->merchant()->create(['email' => 'existing@example.com']);
        $this->actingAs($this->ownerA)->post(route('merchant.staff.store'), [
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => 'Antrein123!',
        ])->assertSessionHasErrors('email');

        // 3. Password too short (< 8 chars)
        $this->actingAs($this->ownerA)->post(route('merchant.staff.store'), [
            'name' => 'Short Pass',
            'email' => 'short@example.com',
            'password' => '1234',
        ])->assertSessionHasErrors('password');

        // 4. Foreign business_id rejected
        $this->actingAs($this->ownerA)->post(route('merchant.staff.store'), [
            'name' => 'Foreign Branch',
            'email' => 'foreign@example.com',
            'password' => 'Antrein123!',
            'business_id' => $this->businessB->id,
        ])->assertSessionHasErrors('business_id');
    }

    /**
     * Test P1-01: Owner can update an operator's login password and encrypted reference.
     */
    public function test_owner_can_update_operator_password(): void
    {
        $operator = User::factory()->merchant()->create([
            'name' => 'Operator Password',
            'email' => 'op.pwd@example.com',
            'password' => Hash::make('OldPassword123'),
        ]);
        $membership = BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->ownerA)->patch(route('merchant.staff.password', $membership), [
            'password' => 'NewPassword999!',
        ]);

        $response->assertRedirect(route('merchant.staff.index'));
        $response->assertSessionHas('status');

        $operator->refresh();
        $membership->refresh();

        $this->assertTrue(Hash::check('NewPassword999!', $operator->password));
        $this->assertSame('NewPassword999!', $membership->getDecryptedStaffPassword());

        $this->assertDatabaseHas('activity_logs', [
            'business_id' => $this->businessA->id,
            'action' => 'staff_password_updated',
        ]);

        // Primary owner password cannot be altered via this endpoint
        $ownerMembership = BusinessMembership::where('user_id', $this->ownerA->id)->firstOrFail();
        $this->actingAs($this->ownerA)->patch(route('merchant.staff.password', $ownerMembership), [
            'password' => 'HackerPass123!',
        ])->assertStatus(403);
    }

    /**
     * Test P1-01: Owner can toggle status and delete operator membership, but cannot delete sole owner.
     */
    public function test_owner_lifecycle_management_for_staff(): void
    {
        $operator = User::factory()->merchant()->create(['name' => 'Operator Tetap']);
        $membership = BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        // 1. Toggle status to inactive
        $this->actingAs($this->ownerA)->patch(route('merchant.staff.toggle-status', $membership))
            ->assertRedirect(route('merchant.staff.index'));
        $membership->refresh();
        $this->assertFalse($membership->is_active);

        // Deactivated operator cannot access dashboard
        $this->actingAs($operator)->get(route('merchant.dashboard'))->assertStatus(403);

        // 2. Toggle status back to active
        $this->actingAs($this->ownerA)->patch(route('merchant.staff.toggle-status', $membership))
            ->assertRedirect(route('merchant.staff.index'));
        $membership->refresh();
        $this->assertTrue($membership->is_active);

        // 3. Delete operator membership
        $this->actingAs($this->ownerA)->delete(route('merchant.staff.destroy', $membership))
            ->assertRedirect(route('merchant.staff.index'));
        $this->assertDatabaseMissing('business_memberships', ['id' => $membership->id]);

        // 4. Primary owner protection: Owner cannot deactivate or delete self
        $ownerMembership = BusinessMembership::where('user_id', $this->ownerA->id)->first();
        $this->actingAs($this->ownerA)->patch(route('merchant.staff.toggle-status', $ownerMembership))
            ->assertStatus(403);
        $this->actingAs($this->ownerA)->delete(route('merchant.staff.destroy', $ownerMembership))
            ->assertStatus(403);
    }

    public function test_owner_can_create_staff_with_specific_counter_or_auto_fallback(): void
    {
        $secondCounter = BusinessCounter::create([
            'business_id' => $this->businessA->id,
            'name' => 'Pos Layanan 2',
            'code' => 'L-02',
            'is_active' => true,
        ]);

        $this->actingAs($this->ownerA)->get(route('merchant.staff.index'))
            ->assertOk()
            ->assertSee('name="business_counter_id"', false)
            ->assertSee('Pos Layanan 2');

        // Explicit counter
        $this->actingAs($this->ownerA)->post(route('merchant.staff.store'), [
            'name' => 'Staf Pos 2',
            'email' => 'staf.loket2@example.com',
            'password' => 'Antrein123!',
            'business_counter_id' => $secondCounter->id,
        ])->assertRedirect(route('merchant.staff.index'));

        $membership2 = BusinessMembership::whereHas('user', fn ($query) => $query->where('email', 'staf.loket2@example.com'))->firstOrFail();
        $this->assertSame([$secondCounter->id], $membership2->assignedCounters()->pluck('business_counters.id')->all());

        // Fallback to first active counter when omitted
        $this->actingAs($this->ownerA)->post(route('merchant.staff.store'), [
            'name' => 'Staf Otomatis',
            'email' => 'staf.otomatis@example.com',
            'password' => 'Antrein123!',
        ])->assertRedirect(route('merchant.staff.index'));

        $membershipAuto = BusinessMembership::whereHas('user', fn ($query) => $query->where('email', 'staf.otomatis@example.com'))->firstOrFail();
        $firstCounter = $this->businessA->counters()->where('is_active', true)->orderBy('id')->firstOrFail();
        $this->assertSame([$firstCounter->id], $membershipAuto->assignedCounters()->pluck('business_counters.id')->all());
    }

    public function test_staff_creation_rejects_counter_from_other_business_or_inactive(): void
    {
        $foreignCounter = $this->businessB->counters()->firstOrFail();
        $inactiveCounter = BusinessCounter::create([
            'business_id' => $this->businessA->id,
            'name' => 'Loket Nonaktif',
            'code' => 'L-09',
            'is_active' => false,
        ]);

        foreach ([$foreignCounter, $inactiveCounter] as $counter) {
            $this->actingAs($this->ownerA)->post(route('merchant.staff.store'), [
                'name' => 'Staf Salah',
                'email' => 'staf.salah@example.com',
                'password' => 'Antrein123!',
                'business_counter_id' => $counter->id,
            ])->assertSessionHasErrors('business_counter_id');
        }

        $this->assertDatabaseMissing('users', ['email' => 'staf.salah@example.com']);
    }
}

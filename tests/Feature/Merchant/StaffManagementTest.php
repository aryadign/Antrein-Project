<?php

namespace Tests\Feature\Merchant;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessMembership;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\StaffInvitation;
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
        $response->assertSee('Kelola Staf & Hak Akses');
        $response->assertSee($this->ownerA->email);
        $response->assertSee('Pemilik Usaha');
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
     * Test P1-02: Operator cannot cancel ticket; only Owner can cancel.
     */
    public function test_operator_cannot_cancel_ticket_but_owner_can(): void
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

        // Operator attempts to cancel -> 403 Forbidden
        $response = $this->actingAs($operator)->post(route('merchant.queues.cancel', $ticket), [
            'reason' => 'Alasan staf',
        ]);
        $response->assertStatus(403);
        $ticket->refresh();
        $this->assertEquals(QueueTicket::STATUS_WAITING, $ticket->status);

        // Owner cancels -> Success
        $ownerResponse = $this->actingAs($this->ownerA)->post(route('merchant.queues.cancel', $ticket), [
            'reason' => 'Pembatalan oleh pemilik',
        ]);
        $ownerResponse->assertRedirect();
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
     * Test P1-01: Owner can issue staff operator invitations.
     */
    public function test_owner_can_issue_staff_invitation(): void
    {
        $response = $this->actingAs($this->ownerA)->post(route('merchant.staff.invitations.store'), [
            'email' => 'calonstaf@example.com',
        ]);

        $response->assertRedirect(route('merchant.staff.index'));
        $response->assertSessionHas('status');
        $response->assertSessionHas('invitation_url');

        $this->assertDatabaseHas('staff_invitations', [
            'business_id' => $this->businessA->id,
            'invited_by_user_id' => $this->ownerA->id,
            'email' => 'calonstaf@example.com',
            'role' => StaffInvitation::ROLE_OPERATOR,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'business_id' => $this->businessA->id,
            'action' => 'staff_invitation_created',
        ]);
    }

    /**
     * Test P1-01: Owner cannot invite self, existing members, or users in another business.
     */
    public function test_invitation_validation_rules(): void
    {
        // 1. Cannot invite owner self
        $this->actingAs($this->ownerA)->post(route('merchant.staff.invitations.store'), [
            'email' => $this->ownerA->email,
        ])->assertSessionHasErrors('email');

        // 2. Cannot invite existing member
        $operator = User::factory()->merchant()->create(['email' => 'existing@example.com']);
        BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $this->actingAs($this->ownerA)->post(route('merchant.staff.invitations.store'), [
            'email' => 'existing@example.com',
        ])->assertSessionHasErrors('email');

        // 3. Cannot invite user from another business
        $otherOperator = User::factory()->merchant()->create(['email' => 'other.member@example.com']);
        BusinessMembership::create([
            'business_id' => $this->businessB->id,
            'user_id' => $otherOperator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $this->actingAs($this->ownerA)->post(route('merchant.staff.invitations.store'), [
            'email' => 'other.member@example.com',
        ])->assertSessionHasErrors('email');

        // 4. Cannot invite admin
        $this->actingAs($this->ownerA)->post(route('merchant.staff.invitations.store'), [
            'email' => $this->admin->email,
        ])->assertSessionHasErrors('email');
    }

    /**
     * Test P1-01: Owner can revoke pending invitation.
     */
    public function test_owner_can_revoke_pending_invitation(): void
    {
        [$plainToken, $tokenHash] = StaffInvitation::generateSecureToken();
        $invitation = StaffInvitation::create([
            'business_id' => $this->businessA->id,
            'invited_by_user_id' => $this->ownerA->id,
            'email' => 'tobe.revoked@example.com',
            'role' => StaffInvitation::ROLE_OPERATOR,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($this->ownerA)->delete(route('merchant.staff.invitations.revoke', $invitation));

        $response->assertRedirect(route('merchant.staff.index'));
        $invitation->refresh();
        $this->assertNotNull($invitation->revoked_at);
        $this->assertTrue($invitation->isRevoked());
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

    /**
     * Test P1-01: Invitation acceptance flow by guest creating new account.
     */
    public function test_guest_can_register_and_accept_invitation(): void
    {
        [$plainToken, $tokenHash] = StaffInvitation::generateSecureToken();
        $invitation = StaffInvitation::create([
            'business_id' => $this->businessA->id,
            'invited_by_user_id' => $this->ownerA->id,
            'email' => 'new.staff@example.com',
            'role' => StaffInvitation::ROLE_OPERATOR,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addDays(3),
        ]);

        // 1. View invitation page
        $showResponse = $this->get(route('invitations.show', ['token' => $plainToken]));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($this->businessA->name);
        $showResponse->assertSee('new.staff@example.com');

        // 2. Accept and create account
        $acceptResponse = $this->post(route('invitations.accept', ['token' => $plainToken]), [
            'name' => 'Staf Baru',
            'password' => 'Antrein123!',
            'password_confirmation' => 'Antrein123!',
        ]);

        $acceptResponse->assertRedirect(route('merchant.dashboard'));

        $user = User::where('email', 'new.staff@example.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        $membership = BusinessMembership::where('user_id', $user->id)->first();
        $this->assertNotNull($membership);
        $this->assertEquals($this->businessA->id, $membership->business_id);
        $this->assertEquals(BusinessMembership::ROLE_OPERATOR, $membership->role);

        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
        $this->assertTrue($invitation->isAccepted());
    }

    /**
     * Test P1-01: Invitation acceptance flow by existing user logging in.
     */
    public function test_existing_user_can_login_and_accept_invitation(): void
    {
        $existingUser = User::factory()->merchant()->create([
            'email' => 'registered.staff@example.com',
            'password' => Hash::make('password123'),
        ]);

        [$plainToken, $tokenHash] = StaffInvitation::generateSecureToken();
        $invitation = StaffInvitation::create([
            'business_id' => $this->businessA->id,
            'invited_by_user_id' => $this->ownerA->id,
            'email' => 'registered.staff@example.com',
            'role' => StaffInvitation::ROLE_OPERATOR,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addDays(3),
        ]);

        $acceptResponse = $this->post(route('invitations.accept', ['token' => $plainToken]), [
            'password' => 'password123',
        ]);

        $acceptResponse->assertRedirect(route('merchant.dashboard'));
        $this->assertAuthenticatedAs($existingUser);

        $membership = BusinessMembership::where('user_id', $existingUser->id)->first();
        $this->assertNotNull($membership);
        $this->assertEquals($this->businessA->id, $membership->business_id);
        $this->assertEquals(BusinessMembership::ROLE_OPERATOR, $membership->role);
    }

    /**
     * Test P1-01: Expired, revoked, or mismatched invitation rejection.
     */
    public function test_invalid_invitation_tokens_are_rejected(): void
    {
        // Expired invitation
        [$expiredToken, $expiredHash] = StaffInvitation::generateSecureToken();
        StaffInvitation::create([
            'business_id' => $this->businessA->id,
            'invited_by_user_id' => $this->ownerA->id,
            'email' => 'expired@example.com',
            'role' => StaffInvitation::ROLE_OPERATOR,
            'token_hash' => $expiredHash,
            'expires_at' => now()->subDay(),
        ]);

        $this->get(route('invitations.show', ['token' => $expiredToken]))
            ->assertSee('Undangan Telah Kedaluwarsa');

        // Mismatched logged-in user
        [$validToken, $validHash] = StaffInvitation::generateSecureToken();
        StaffInvitation::create([
            'business_id' => $this->businessA->id,
            'invited_by_user_id' => $this->ownerA->id,
            'email' => 'target@example.com',
            'role' => StaffInvitation::ROLE_OPERATOR,
            'token_hash' => $validHash,
            'expires_at' => now()->addDays(3),
        ]);

        $otherUser = User::factory()->merchant()->create(['email' => 'wrong.account@example.com']);
        $this->actingAs($otherUser)
            ->get(route('invitations.show', ['token' => $validToken]))
            ->assertSee('Akun Tidak Sesuai');
    }
}

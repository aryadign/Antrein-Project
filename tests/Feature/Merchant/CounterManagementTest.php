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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerA;
    protected Business $businessA;
    protected Service $serviceA;
    protected DailyQueue $dailyQueueA;

    protected User $ownerB;
    protected Business $businessB;
    protected Service $serviceB;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Barbershop & Salon',
            'slug' => 'barbershop-salon',
            'is_active' => true,
        ]);

        // Setup Business A
        $this->ownerA = User::factory()->merchant()->create();
        $this->businessA = Business::create([
            'user_id' => $this->ownerA->id,
            'category_id' => $category->id,
            'name' => 'Barbershop Mantap A',
            'slug' => 'barbershop-mantap-a',
            'status' => Business::STATUS_BUKA,
            'timezone' => 'Asia/Makassar',
        ]);
        $this->serviceA = Service::create([
            'business_id' => $this->businessA->id,
            'name' => 'Potong Rambut',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        $this->dailyQueueA = DailyQueue::create([
            'business_id' => $this->businessA->id,
            'queue_date' => now()->setTimezone('Asia/Makassar')->toDateString(),
            'current_number' => 0,
        ]);

        // Setup Business B (for multi-tenant isolation tests)
        $this->ownerB = User::factory()->merchant()->create();
        $this->businessB = Business::create([
            'user_id' => $this->ownerB->id,
            'category_id' => $category->id,
            'name' => 'Klinik Sehat B',
            'slug' => 'klinik-sehat-b',
            'status' => Business::STATUS_BUKA,
            'timezone' => 'Asia/Makassar',
        ]);
        $this->serviceB = Service::create([
            'business_id' => $this->businessB->id,
            'name' => 'Konsultasi Dokter',
            'duration_minutes' => 20,
            'is_active' => true,
        ]);
    }

    /**
     * Test M13-01: Default counter Loket 1 is automatically provisioned on business creation.
     */
    public function test_default_counter_created_automatically_on_business_creation(): void
    {
        $counter = $this->businessA->counters()->first();

        $this->assertNotNull($counter);
        $this->assertEquals('Loket 1', $counter->name);
        $this->assertEquals('L-01', $counter->code);
        $this->assertTrue($counter->is_active);
    }

    /**
     * Test M13-02: Owner can access counter management page.
     */
    public function test_owner_can_access_counter_management_page(): void
    {
        $response = $this->actingAs($this->ownerA)->get(route('merchant.counters.index'));

        $response->assertStatus(200);
        $response->assertSee('Kelola Loket Pelayanan');
        $response->assertSee('Loket 1');
        $response->assertSee('L-01');
    }

    /**
     * Test M13-03: Operator cannot access counter management page (restricted to Owner).
     */
    public function test_operator_cannot_access_counter_management_page(): void
    {
        $operator = User::factory()->merchant()->create();
        BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($operator)->get(route('merchant.counters.index'));

        $response->assertStatus(403);
    }

    /**
     * Test M13-04: Owner can create a new counter with unique code.
     */
    public function test_owner_can_create_new_counter_with_unique_code(): void
    {
        $response = $this->actingAs($this->ownerA)->post(route('merchant.counters.store'), [
            'name' => 'Loket 2',
            'code' => 'l-02', // Will be uppercase L-02
            'description' => 'Loket khusus pembayaran cepat',
        ]);

        $response->assertRedirect(route('merchant.counters.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('business_counters', [
            'business_id' => $this->businessA->id,
            'name' => 'Loket 2',
            'code' => 'L-02',
            'description' => 'Loket khusus pembayaran cepat',
            'is_active' => true,
        ]);

        // Activity log recorded
        $this->assertDatabaseHas('activity_logs', [
            'business_id' => $this->businessA->id,
            'action' => 'counter_created',
        ]);

        // Duplicate code in same business rejected
        $dupResponse = $this->actingAs($this->ownerA)->post(route('merchant.counters.store'), [
            'name' => 'Loket Duplikat',
            'code' => 'L-02',
        ]);
        $dupResponse->assertSessionHasErrors(['code']);
    }

    /**
     * Test M13-05: Owner can update counter details.
     */
    public function test_owner_can_update_counter_details(): void
    {
        $counter = $this->businessA->counters()->first();

        $response = $this->actingAs($this->ownerA)->put(route('merchant.counters.update', $counter), [
            'name' => 'Loket Kasir & CS',
            'code' => 'L-CS',
            'description' => 'Pelayanan komprehensif',
        ]);

        $response->assertRedirect(route('merchant.counters.index'));
        $counter->refresh();
        $this->assertEquals('Loket Kasir & CS', $counter->name);
        $this->assertEquals('L-CS', $counter->code);
        $this->assertEquals('Pelayanan komprehensif', $counter->description);
    }

    /**
     * Test M13-06: Owner can toggle counter active status.
     */
    public function test_owner_can_toggle_counter_active_status(): void
    {
        // Create second counter first so we can deactivate one
        $counter2 = BusinessCounter::create([
            'business_id' => $this->businessA->id,
            'name' => 'Loket 2',
            'code' => 'L-02',
            'is_active' => true,
        ]);

        // Deactivate Loket 2
        $response = $this->actingAs($this->ownerA)->patch(route('merchant.counters.toggle-status', $counter2));
        $response->assertRedirect();
        $counter2->refresh();
        $this->assertFalse($counter2->is_active);

        // Reactivate Loket 2
        $this->actingAs($this->ownerA)->patch(route('merchant.counters.toggle-status', $counter2));
        $counter2->refresh();
        $this->assertTrue($counter2->is_active);
    }

    /**
     * Test M13-07: Guard: Cannot deactivate sole active counter.
     */
    public function test_cannot_deactivate_last_active_counter(): void
    {
        $counter = $this->businessA->counters()->first();

        $response = $this->actingAs($this->ownerA)->patch(route('merchant.counters.toggle-status', $counter));
        $response->assertSessionHasErrors(['counter_error']);
        $counter->refresh();
        $this->assertTrue($counter->is_active);
    }

    /**
     * Test M13-08: Guard: Cannot deactivate counter with active ticket.
     */
    public function test_cannot_deactivate_counter_with_active_ticket(): void
    {
        $counter1 = $this->businessA->counters()->first();
        $counter2 = BusinessCounter::create([
            'business_id' => $this->businessA->id,
            'name' => 'Loket 2',
            'code' => 'L-02',
            'is_active' => true,
        ]);

        QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->dailyQueueA->id,
            'service_id' => $this->serviceA->id,
            'counter_id' => $counter1->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Budi',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'access_token' => 'tok11111111111111111111111111111111',
            'called_at' => now(),
        ]);

        $response = $this->actingAs($this->ownerA)->patch(route('merchant.counters.toggle-status', $counter1));
        $response->assertSessionHasErrors(['counter_error']);
        $counter1->refresh();
        $this->assertTrue($counter1->is_active);
    }

    /**
     * Test M13-09: Owner can delete counter without active tickets when multiple counters exist.
     */
    public function test_owner_can_delete_counter(): void
    {
        $counter2 = BusinessCounter::create([
            'business_id' => $this->businessA->id,
            'name' => 'Loket 2',
            'code' => 'L-02',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->ownerA)->delete(route('merchant.counters.destroy', $counter2));
        $response->assertRedirect(route('merchant.counters.index'));

        $this->assertDatabaseMissing('business_counters', [
            'id' => $counter2->id,
        ]);
    }

    /**
     * Test M13-10: Owner can assign and unassign operator to counter.
     */
    public function test_owner_can_assign_and_unassign_operator(): void
    {
        $operator = User::factory()->merchant()->create();
        $membership = BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $counter2 = BusinessCounter::create([
            'business_id' => $this->businessA->id,
            'name' => 'Loket 2',
            'code' => 'L-02',
            'is_active' => true,
        ]);

        // Assign operator to counter2
        $assignResponse = $this->actingAs($this->ownerA)->post(route('merchant.counters.assign-operator', $counter2), [
            'business_membership_id' => $membership->id,
        ]);
        $assignResponse->assertRedirect();

        $this->assertDatabaseHas('counter_operator_assignments', [
            'business_counter_id' => $counter2->id,
            'business_membership_id' => $membership->id,
        ]);

        // Unassign operator
        $unassignResponse = $this->actingAs($this->ownerA)->delete(route('merchant.counters.unassign-operator', [$counter2, $membership]));
        $unassignResponse->assertRedirect();

        $this->assertDatabaseMissing('counter_operator_assignments', [
            'business_counter_id' => $counter2->id,
            'business_membership_id' => $membership->id,
        ]);
    }

    /**
     * Test M13-11: Operator can only operate assigned counters; Owner can operate all active counters.
     */
    public function test_operator_permission_guard_on_counters(): void
    {
        $operator = User::factory()->merchant()->create();
        $membership = BusinessMembership::create([
            'business_id' => $this->businessA->id,
            'user_id' => $operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);

        $counter1 = $this->businessA->counters()->first();
        $counter2 = BusinessCounter::create([
            'business_id' => $this->businessA->id,
            'name' => 'Loket 2',
            'code' => 'L-02',
            'is_active' => true,
        ]);

        // Detach operator from counter2 if attached
        $counter2->assignedMemberships()->detach($membership->id);

        $this->assertTrue($this->ownerA->canOperateCounter($counter1));
        $this->assertTrue($this->ownerA->canOperateCounter($counter2));

        $this->assertTrue($operator->canOperateCounter($counter1));
        $this->assertFalse($operator->canOperateCounter($counter2));
    }

    /**
     * Test M13-12: Parallel ticket calling on separate counters (both counters active simultaneously).
     */
    public function test_parallel_ticket_calling_on_separate_counters(): void
    {
        $counter1 = $this->businessA->counters()->first();
        $counter2 = BusinessCounter::create([
            'business_id' => $this->businessA->id,
            'name' => 'Loket 2',
            'code' => 'L-02',
            'is_active' => true,
        ]);

        // Two waiting tickets
        $ticket1 = QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->dailyQueueA->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan 1',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'tok11111111111111111111111111111111',
        ]);

        $ticket2 = QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->dailyQueueA->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Pelanggan 2',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'tok22222222222222222222222222222222',
        ]);

        // 1. Loket 1 calls next -> gets Ticket 1
        $response1 = $this->actingAs($this->ownerA)->post(route('merchant.queues.call-next'), [
            'counter_id' => $counter1->id,
        ]);
        $response1->assertRedirect();

        $ticket1->refresh();
        $this->assertEquals(QueueTicket::STATUS_DIPANGGIL, $ticket1->status);
        $this->assertEquals($counter1->id, $ticket1->counter_id);

        // 2. Loket 2 calls next IN PARALLEL while Ticket 1 is still active -> SUCCESS, gets Ticket 2
        $response2 = $this->actingAs($this->ownerA)->post(route('merchant.queues.call-next'), [
            'counter_id' => $counter2->id,
        ]);
        $response2->assertRedirect();

        $ticket2->refresh();
        $this->assertEquals(QueueTicket::STATUS_DIPANGGIL, $ticket2->status);
        $this->assertEquals($counter2->id, $ticket2->counter_id);

        // Verify both counters have an active ticket simultaneously
        $this->assertEquals(1, $counter1->tickets()->whereIn('status', [QueueTicket::STATUS_DIPANGGIL, QueueTicket::STATUS_SEDANG_DILAYANI])->count());
        $this->assertEquals(1, $counter2->tickets()->whereIn('status', [QueueTicket::STATUS_DIPANGGIL, QueueTicket::STATUS_SEDANG_DILAYANI])->count());
    }

    /**
     * Test M13-13: Single active ticket rule holds per counter (cannot call next on a counter that already has an active ticket).
     */
    public function test_single_active_ticket_rule_holds_per_counter(): void
    {
        $counter1 = $this->businessA->counters()->first();

        // Two waiting tickets
        QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->dailyQueueA->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Pelanggan 1',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'tok11111111111111111111111111111111',
        ]);

        QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->dailyQueueA->id,
            'service_id' => $this->serviceA->id,
            'ticket_number' => 'A-002',
            'sequence_number' => 2,
            'customer_name' => 'Pelanggan 2',
            'source' => QueueTicket::SOURCE_WALK_IN,
            'status' => QueueTicket::STATUS_MENUNGGU,
            'access_token' => 'tok22222222222222222222222222222222',
        ]);

        // Call next on Counter 1
        $this->actingAs($this->ownerA)->post(route('merchant.queues.call-next'), [
            'counter_id' => $counter1->id,
        ])->assertRedirect();

        // Attempting to call next AGAIN on Counter 1 while ticket is active is BLOCKED
        $response = $this->actingAs($this->ownerA)->post(route('merchant.queues.call-next'), [
            'counter_id' => $counter1->id,
        ]);
        $response->assertSessionHasErrors(['queue_error']);
    }

    /**
     * Test M13-14: Strict global FIFO is preserved across counters.
     */
    public function test_strict_global_fifo_preserved_across_counters(): void
    {
        $counter1 = $this->businessA->counters()->first();
        $counter2 = BusinessCounter::create([
            'business_id' => $this->businessA->id,
            'name' => 'Loket 2',
            'code' => 'L-02',
            'is_active' => true,
        ]);

        // 4 tickets in queue sequence 1, 2, 3, 4
        for ($i = 1; $i <= 4; $i++) {
            QueueTicket::create([
                'business_id' => $this->businessA->id,
                'daily_queue_id' => $this->dailyQueueA->id,
                'service_id' => $this->serviceA->id,
                'ticket_number' => sprintf('A-%03d', $i),
                'sequence_number' => $i,
                'customer_name' => "Pelanggan {$i}",
                'source' => QueueTicket::SOURCE_WALK_IN,
                'status' => QueueTicket::STATUS_MENUNGGU,
                'access_token' => str_repeat((string) $i, 32),
            ]);
        }

        // Loket 2 calls first -> MUST get ticket #1
        $this->actingAs($this->ownerA)->post(route('merchant.queues.call-next'), ['counter_id' => $counter2->id]);
        $t1 = QueueTicket::where('sequence_number', 1)->first();
        $this->assertEquals($counter2->id, $t1->counter_id);
        $this->assertEquals(QueueTicket::STATUS_DIPANGGIL, $t1->status);

        // Loket 1 calls next -> MUST get ticket #2
        $this->actingAs($this->ownerA)->post(route('merchant.queues.call-next'), ['counter_id' => $counter1->id]);
        $t2 = QueueTicket::where('sequence_number', 2)->first();
        $this->assertEquals($counter1->id, $t2->counter_id);
        $this->assertEquals(QueueTicket::STATUS_DIPANGGIL, $t2->status);

        // Loket 2 finishes ticket #1 and calls next -> MUST get ticket #3
        $this->actingAs($this->ownerA)->post(route('merchant.queues.start-service', $t1));
        $this->actingAs($this->ownerA)->post(route('merchant.queues.complete', $t1));
        $this->actingAs($this->ownerA)->post(route('merchant.queues.call-next'), ['counter_id' => $counter2->id]);
        $t3 = QueueTicket::where('sequence_number', 3)->first();
        $this->assertEquals($counter2->id, $t3->counter_id);
        $this->assertEquals(QueueTicket::STATUS_DIPANGGIL, $t3->status);
    }

    /**
     * Test M13-15: Customer tracking API returns counter name and code without exposing internal ID.
     */
    public function test_customer_tracking_returns_counter_name_without_internal_id(): void
    {
        $counter = $this->businessA->counters()->first();

        $ticket = QueueTicket::create([
            'business_id' => $this->businessA->id,
            'daily_queue_id' => $this->dailyQueueA->id,
            'service_id' => $this->serviceA->id,
            'counter_id' => $counter->id,
            'ticket_number' => 'A-001',
            'sequence_number' => 1,
            'customer_name' => 'Siti Rahma',
            'customer_phone' => '081234567890',
            'source' => QueueTicket::SOURCE_ONLINE,
            'status' => QueueTicket::STATUS_DIPANGGIL,
            'access_token' => 'custtracktok12345678901234567890',
            'called_at' => now(),
        ]);

        $response = $this->getJson(route('customer.queue.track', ['token' => $ticket->access_token]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'ticket_number' => 'A-001',
                'status' => 'dipanggil',
                'counter_name' => 'Loket 1',
                'counter_code' => 'L-01',
            ],
        ]);

        // Security check: Never expose raw counter primary key
        $response->assertJsonMissing(['counter_id' => $counter->id]);
    }

    /**
     * Test M13-16: Active counter session persistence.
     */
    public function test_counter_selection_persists_in_session(): void
    {
        $counter2 = BusinessCounter::create([
            'business_id' => $this->businessA->id,
            'name' => 'Loket 2',
            'code' => 'L-02',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->ownerA)->post(route('merchant.queues.select-counter'), [
            'counter_id' => $counter2->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('merchant_active_counter_id', $counter2->id);
    }

    /**
     * Test M13-17: Multi-tenant isolation for counters.
     */
    public function test_multi_tenancy_isolation_for_counters(): void
    {
        $counterA = $this->businessA->counters()->first();
        $counterB = $this->businessB->counters()->first();

        // Owner A cannot edit or delete Counter B
        $this->actingAs($this->ownerA)->put(route('merchant.counters.update', $counterB), [
            'name' => 'Hack Counter',
            'code' => 'L-HACK',
        ])->assertStatus(403);

        $this->actingAs($this->ownerA)->delete(route('merchant.counters.destroy', $counterB))
            ->assertStatus(403);

        // Owner A cannot call next on Counter B
        $response = $this->actingAs($this->ownerA)->post(route('merchant.queues.call-next'), [
            'counter_id' => $counterB->id,
        ]);
        $response->assertSessionHasErrors(['queue_error']);
    }
}

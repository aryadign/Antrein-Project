<?php

namespace Tests\Feature\Upgrades;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\BusinessHour;
use App\Models\BusinessMembership;
use App\Models\CustomerReview;
use App\Models\QueueStatusHistory;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingSlotService;
use App\Services\QueueService;
use App\Services\WaitingTimeEstimationService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NineUpgradesCoverageTest extends TestCase
{
    use RefreshDatabase;

    private BusinessCategory $coffeeCategory;

    private User $owner;

    private Business $branchA;

    private Business $branchB;

    private User $operatorA;

    private BusinessCounter $counterA1;

    private BusinessCounter $counterA2;

    private Service $espressoService;

    private Service $slowBrewService;

    protected function setUp(): void
    {
        parent::setUp();

        // Pin local operating clock to 10:15 WITA so full-day hourly booking slots exist
        $this->travelTo(Carbon::today('Asia/Makassar')->setTime(10, 15));

        $this->coffeeCategory = BusinessCategory::create([
            'name' => 'Kafe & Kedai Kopi',
            'slug' => 'kafe-kedai-kopi',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->merchant()->create([
            'name' => 'Owner Kopi Group',
            'email' => 'owner.kopigroup@example.com',
        ]);

        $this->branchA = Business::create([
            'user_id' => $this->owner->id,
            'category_id' => $this->coffeeCategory->id,
            'name' => 'Kopi Senja Canggu',
            'slug' => 'kopi-senja-canggu',
            'area' => 'Canggu',
            'address' => 'Jl. Pantai Berawa No. 18, Canggu',
            'phone' => '081234567801',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'approval_status' => Business::APPROVAL_APPROVED,
            'is_active' => true,
            'is_display_enabled' => true,
            'display_token_hash' => hash('sha256', 'canggu-display-token'),
            'display_token_created_at' => now(),
        ]);

        $this->branchA->refresh();
        $organization = $this->branchA->organization;

        $this->branchB = Business::create([
            'organization_id' => $organization->id,
            'user_id' => $this->owner->id,
            'category_id' => $this->coffeeCategory->id,
            'name' => 'Kopi Senja Ubud',
            'slug' => 'kopi-senja-ubud',
            'area' => 'Ubud',
            'address' => 'Jl. Raya Ubud No. 22, Ubud',
            'phone' => '081234567802',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'approval_status' => Business::APPROVAL_APPROVED,
            'is_active' => true,
        ]);

        foreach ([$this->branchA, $this->branchB] as $branch) {
            for ($d = 0; $d <= 6; $d++) {
                BusinessHour::create([
                    'business_id' => $branch->id,
                    'day_of_week' => $d,
                    'open_time' => '08:00:00',
                    'close_time' => '22:00:00',
                    'is_closed' => false,
                ]);
            }
        }

        // Branch A gets two active service units (Loket 1 auto-created + Loket 2)
        $this->counterA1 = $this->branchA->counters()->orderBy('id')->firstOrFail();
        $this->counterA2 = BusinessCounter::create([
            'business_id' => $this->branchA->id,
            'name' => 'Loket 2',
            'code' => 'L-02',
            'is_active' => true,
        ]);

        $this->operatorA = User::factory()->merchant()->create([
            'name' => 'Barista Operator',
            'email' => 'operator.canggu@example.com',
        ]);

        $membership = BusinessMembership::create([
            'organization_id' => $organization->id,
            'business_id' => $this->branchA->id,
            'user_id' => $this->operatorA->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
        ]);
        $membership->assignedCounters()->sync([$this->counterA1->id, $this->counterA2->id]);

        $this->espressoService = Service::create([
            'business_id' => $this->branchA->id,
            'name' => 'Manual Brew Espresso',
            'duration_minutes' => 15, // 60/15 * 2 counters = 8 slots per hour
            'daily_quota' => 50,
            'is_active' => true,
        ]);

        $this->slowBrewService = Service::create([
            'business_id' => $this->branchA->id,
            'name' => 'Signature Cold Drip',
            'duration_minutes' => 30, // 60/30 * 2 counters = 4 slots per hour
            'daily_quota' => 30,
            'is_active' => true,
        ]);
    }

    public function test_booking_slot_capacity_and_validation_rejects_started_or_full_slots(): void
    {
        $queueService = app(QueueService::class);
        $slotService = app(BookingSlotService::class);
        $dailyQueue = $queueService->getOrCreateDailyQueue($this->branchA);

        $this->assertSame(8, $slotService->calculateSlotCapacity($this->branchA, $this->espressoService));
        $this->assertSame(4, $slotService->calculateSlotCapacity($this->branchA, $this->slowBrewService));

        $slots = $slotService->getSlotsForService($this->branchA, $this->espressoService, $dailyQueue);
        $startedSlot = $slots->firstWhere('slot_key', '10:00');
        $upcomingSlot = $slots->firstWhere('slot_key', '11:00');

        $this->assertNotNull($startedSlot);
        $this->assertTrue($startedSlot['is_started']);
        $this->assertFalse($startedSlot['is_available']);

        $this->assertNotNull($upcomingSlot);
        $this->assertFalse($upcomingSlot['is_started']);
        $this->assertTrue($upcomingSlot['is_available']);

        // Online registration at 10:00 (already started at 10:15) must be blocked
        try {
            $queueService->generateOnlineTicket(
                business: $this->branchA,
                service: $this->espressoService,
                customerName: 'Tamu Terlambat',
                bookingSlot: '10:00'
            );
            $this->fail('Expected DomainException when booking an already-started slot.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('sudah dimulai', $e->getMessage());
        }

        // Fill 12:00 slot capacity (4 tickets) for slowBrewService (30 min * 2 counters)
        for ($i = 1; $i <= 4; $i++) {
            $queueService->generateOnlineTicket(
                business: $this->branchA,
                service: $this->slowBrewService,
                customerName: "Pelanggan $i",
                bookingSlot: '12:00'
            );
        }

        // 5th ticket into full 12:00 slot must be blocked
        try {
            $queueService->generateOnlineTicket(
                business: $this->branchA,
                service: $this->slowBrewService,
                customerName: 'Pelanggan Ke-5',
                bookingSlot: '12:00'
            );
            $this->fail('Expected DomainException when booking a full slot.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('sudah penuh', $e->getMessage());
        }
    }

    public function test_walk_in_assigns_nearest_upcoming_slot_and_call_next_enforces_booking_slot_fifo(): void
    {
        $queueService = app(QueueService::class);

        // Customer 1 books later slot 13:00 first (sequence 1)
        $laterSlotFirst = $queueService->generateOnlineTicket(
            business: $this->branchA,
            service: $this->espressoService,
            customerName: 'Slot 13 Pertama',
            bookingSlot: '13:00'
        );

        // Customer 2 books earlier slot 11:00 second (sequence 2)
        $earlierSlotSecond = $queueService->generateOnlineTicket(
            business: $this->branchA,
            service: $this->espressoService,
            customerName: 'Slot 11 Kedua',
            bookingSlot: '11:00'
        );

        // Customer 3 registers walk-in -> should be assigned nearest upcoming slot (11:00), sequence 3
        $walkIn = $queueService->generateWalkInTicket(
            business: $this->branchA,
            service: $this->espressoService,
            customerName: 'Walk-in Dekat',
            operator: $this->operatorA
        );

        $this->assertSame('TKT-001', $laterSlotFirst->ticket_number);
        $this->assertSame('TKT-002', $earlierSlotSecond->ticket_number);
        $this->assertSame('TKT-003', $walkIn->ticket_number);
        $this->assertSame('11:00', $walkIn->bookingSlotLabel());

        // Strict Booking-Slot FIFO: 11:00 seq 2 (TKT-002) is called before 11:00 seq 3 (TKT-003) and 13:00 seq 1 (TKT-001)
        $firstCalled = $queueService->callNext($this->branchA, $this->operatorA, $this->counterA1);
        $this->assertSame($earlierSlotSecond->id, $firstCalled->id);

        $secondCalled = $queueService->callNext($this->branchA, $this->operatorA, $this->counterA2);
        $this->assertSame($walkIn->id, $secondCalled->id);

        $queueService->completeTicket($firstCalled->fresh(), $this->operatorA, counter: $this->counterA1);

        $thirdCalled = $queueService->callNext($this->branchA, $this->operatorA, $this->counterA1);
        $this->assertSame($laterSlotFirst->id, $thirdCalled->id);
    }

    public function test_auto_no_show_expires_stale_called_tickets_after_15_minutes_and_releases_counter(): void
    {
        $queueService = app(QueueService::class);

        $ticket1 = $queueService->generateOnlineTicket($this->branchA, $this->espressoService, 'Pelanggan Lupa', bookingSlot: '11:00');
        $ticket2 = $queueService->generateOnlineTicket($this->branchA, $this->espressoService, 'Pelanggan Siap', bookingSlot: '11:00');

        $called = $queueService->callNext($this->branchA, $this->operatorA, $this->counterA1);
        $this->assertSame($ticket1->id, $called->id);

        // Travel 16 minutes ahead without starting service
        $this->travel(16)->minutes();

        $this->artisan('queues:expire-noshow')
            ->assertExitCode(0);

        $ticket1->refresh();
        $this->assertSame(QueueTicket::STATUS_TIDAK_HADIR, $ticket1->status);
        $this->assertNotNull($ticket1->skipped_at);

        $history = QueueStatusHistory::where('queue_ticket_id', $ticket1->id)
            ->where('to_status', QueueTicket::STATUS_TIDAK_HADIR)
            ->first();
        $this->assertNotNull($history);
        $this->assertSame(QueueStatusHistory::ACTOR_SYSTEM, $history->actor_type);

        // Counter 1 lock must now be released so ticket2 can be called cleanly
        $nextCalled = $queueService->callNext($this->branchA, $this->operatorA, $this->counterA1);
        $this->assertSame($ticket2->id, $nextCalled->id);
    }

    public function test_delay_shift_notice_is_computed_when_earlier_slot_has_leftovers(): void
    {
        $queueService = app(QueueService::class);
        $estimationService = app(WaitingTimeEstimationService::class);

        // Create two 30-min tickets in 11:00 and one ticket in 12:00, and move clock to 12:05
        $queueService->generateOnlineTicket($this->branchA, $this->slowBrewService, 'Sisa A', bookingSlot: '11:00');
        $queueService->generateOnlineTicket($this->branchA, $this->slowBrewService, 'Sisa B', bookingSlot: '11:00');
        $queueService->generateOnlineTicket($this->branchA, $this->slowBrewService, 'Sisa C', bookingSlot: '11:00');
        $ticketSlot12 = $queueService->generateOnlineTicket($this->branchA, $this->slowBrewService, 'Slot 12', bookingSlot: '12:00');

        $this->travelTo(Carbon::today('Asia/Makassar')->setTime(12, 5));

        $estimate = $estimationService->estimateScheduleForTicket($ticketSlot12);

        $this->assertTrue($estimate['available']);
        $this->assertSame(4, $estimate['queue_position']);
        $this->assertSame(3, $estimate['people_ahead']);
        $this->assertTrue($estimate['has_earlier_slot_leftovers']);
        $this->assertTrue($estimate['has_delay_shift']);
        $this->assertNotNull($estimate['shift_notice']);
        $this->assertStringContainsString('sisa antrean dari slot sebelumnya', $estimate['shift_notice']);
    }

    public function test_customer_views_and_json_apis_strictly_hide_now_serving_and_other_customers_tickets(): void
    {
        $queueService = app(QueueService::class);
        $customer = User::factory()->customer()->create(['name' => 'Pemilik Tiket Sendiri']);

        $otherTicket = $queueService->generateOnlineTicket($this->branchA, $this->espressoService, 'Rahasia Orang Lain', bookingSlot: '11:00');
        $queueService->callNext($this->branchA, $this->operatorA, $this->counterA1);

        $ownTicket = $queueService->generateOnlineTicket(
            business: $this->branchA,
            service: $this->espressoService,
            customerName: $customer->name,
            customer: $customer,
            bookingSlot: '11:00'
        );

        // Store JSON & HTML must omit other customer's ticket number & now_serving
        $storeJson = $this->getJson(route('customer.queue', $this->branchA->slug));
        $storeJson->assertOk()
            ->assertJsonMissingPath('data.now_serving')
            ->assertJsonPath('data.waiting_count', 1);

        $storeHtml = $this->get(route('customer.queue', $this->branchA->slug));
        $storeHtml->assertOk()
            ->assertDontSee($otherTicket->ticket_number)
            ->assertDontSee('Rahasia Orang Lain');

        // Track JSON & HTML must show own ticket + schedule_estimate without leaking now_serving
        $trackJson = $this->getJson(route('customer.queue.track', $ownTicket->access_token));
        $trackJson->assertOk()
            ->assertJsonPath('data.ticket_number', $ownTicket->ticket_number)
            ->assertJsonMissingPath('data.now_serving')
            ->assertJsonStructure(['data' => ['schedule_estimate' => ['wait_range_label', 'estimated_serve_time_label']]]);

        $trackHtml = $this->get(route('customer.queue.track', $ownTicket->access_token));
        $trackHtml->assertOk()
            ->assertSee($ownTicket->ticket_number)
            ->assertDontSee($otherTicket->ticket_number);
    }

    public function test_area_filtering_and_shopee_style_reviews_filter_and_sort_work(): void
    {
        // Area filter matches Canggu & excludes Ubud; invalid area falls back safely to all
        $this->get(route('customer.search', ['daerah' => 'Canggu']))
            ->assertOk()
            ->assertSee('Kopi Senja Canggu')
            ->assertDontSee('Kopi Senja Ubud');

        $this->get(route('customer.search', ['daerah' => 'PlanetMars']))
            ->assertOk()
            ->assertSee('Kopi Senja Canggu')
            ->assertSee('Kopi Senja Ubud');

        // Seed diverse reviews on branch A for Shopee-style filter & sort
        $queueService = app(QueueService::class);
        $dq = $queueService->getOrCreateDailyQueue($this->branchA);

        $createCompletedAndReview = function (int $seq, int $rating, ?string $comment) use ($dq): void {
            $ticket = QueueTicket::create([
                'business_id' => $this->branchA->id,
                'daily_queue_id' => $dq->id,
                'service_id' => $this->espressoService->id,
                'ticket_number' => sprintf('TKT-%03d', $seq),
                'sequence_number' => $seq,
                'customer_name' => "Pengulas $seq",
                'source' => QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_SELESAI,
                'completed_at' => now()->subMinutes(10 - $seq),
                'access_token' => "token-review-$seq-".uniqid(),
            ]);

            CustomerReview::create([
                'business_id' => $this->branchA->id,
                'queue_ticket_id' => $ticket->id,
                'service_id' => $this->espressoService->id,
                'rating' => $rating,
                'comment' => $comment,
            ]);
        };

        $createCompletedAndReview(90, 5, 'Kopi juara dan pelayanan super cepat.');
        $createCompletedAndReview(91, 2, 'Agak lama menunggu di jam ramai.');
        $createCompletedAndReview(92, 5, null);

        $this->get(route('customer.queue', ['slug' => $this->branchA->slug, 'bintang' => 2]))
            ->assertOk()
            ->assertSee('Agak lama menunggu di jam ramai.')
            ->assertDontSee('Kopi juara dan pelayanan super cepat.');

        $this->get(route('customer.queue', ['slug' => $this->branchA->slug, 'dengan_komentar' => 1, 'urut_ulasan' => 'rating_tinggi']))
            ->assertOk()
            ->assertSee('Kopi juara dan pelayanan super cepat.')
            ->assertSee('Dengan Komentar');
    }

    public function test_combined_multi_branch_organization_dashboard_and_neutral_ui_surfaces(): void
    {
        $queueService = app(QueueService::class);
        $serviceB = Service::create([
            'business_id' => $this->branchB->id,
            'name' => 'Espresso Ubud',
            'duration_minutes' => 15,
            'daily_quota' => 40,
            'is_active' => true,
        ]);

        $queueService->generateOnlineTicket($this->branchA, $this->espressoService, 'Tamu Canggu', bookingSlot: '11:00');
        $queueService->generateOnlineTicket($this->branchB, $serviceB, 'Tamu Ubud', bookingSlot: '11:00');

        // Switch owner scope to "all" (Semua Cabang) and inspect combined dashboard
        $this->actingAs($this->owner)
            ->post(route('merchant.branches.switch'), ['business_id' => 'all'])
            ->assertRedirect(route('merchant.dashboard'));

        $dashboard = $this->actingAs($this->owner)->get(route('merchant.dashboard'));
        $dashboard->assertOk()
            ->assertSee('Ringkasan Gabungan')
            ->assertSee('Kopi Senja Canggu')
            ->assertSee('Kopi Senja Ubud')
            ->assertSee('Layanan')
            ->assertDontSee('M10.5 3.75H6a2.25'); // No legacy scissors SVG on Layanan sidebar

        // Light / brand-700 Layar TV renders cleanly on operator display management & public TV view (forbidden for owner)
        $this->actingAs($this->owner)
            ->get(route('merchant.display.index'))
            ->assertForbidden();

        $this->actingAs($this->operatorA)
            ->get(route('merchant.display.index'))
            ->assertOk()
            ->assertSee('Layar TV');

        $this->get(route('display.show', 'canggu-display-token'))
            ->assertOk()
            ->assertSee('Kopi Senja Canggu')
            ->assertDontSee('bg-slate-950');

        // Direct staff account creation (no invitation URLs)
        $staffPage = $this->actingAs($this->owner)->get(route('merchant.staff.index'));
        $staffPage->assertOk()
            ->assertDontSee('/invite/');
    }
}

<?php

namespace Tests\Feature\Upgrades;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessCounter;
use App\Models\BusinessHour;
use App\Models\BusinessMembership;
use App\Models\DailyQueue;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingSlotService;
use App\Services\DisplayService;
use App\Services\WaitingTimeEstimationService;
use Carbon\Carbon;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\BusinessCategorySeeder;
use Database\Seeders\DevelopmentMerchantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationLogoAndGradientRefinementsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $operator;

    private Business $business;

    private BusinessCounter $counter1;

    private BusinessCounter $counter2;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::today('Asia/Makassar')->setTime(10, 15));

        $category = BusinessCategory::create([
            'name' => 'Barbershop & Pangkas Rambut',
            'slug' => 'barbershop-pangkas-rambut',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->merchant()->create([
            'name' => 'Pemilik Station Test',
            'email' => 'owner.station@example.com',
        ]);

        $this->business = Business::create([
            'user_id' => $this->owner->id,
            'category_id' => $category->id,
            'name' => 'Garasi Test Station',
            'slug' => 'garasi-test-station',
            'area' => 'Denpasar',
            'address' => 'Jl. Teuku Umar No. 10, Denpasar',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'approval_status' => Business::APPROVAL_APPROVED,
            'is_active' => true,
            'use_stations' => true,
            'is_display_enabled' => true,
            'display_token_hash' => hash('sha256', 'station-display-token'),
            'display_token_created_at' => now(),
        ]);

        for ($d = 0; $d <= 6; $d++) {
            BusinessHour::create([
                'business_id' => $this->business->id,
                'day_of_week' => $d,
                'open_time' => '08:00:00',
                'close_time' => '21:00:00',
                'is_closed' => false,
            ]);
        }

        $this->counter1 = BusinessCounter::updateOrCreate(
            ['business_id' => $this->business->id, 'code' => 'L-01'],
            [
                'name' => 'Kursi 1',
                'is_active' => true,
            ]
        );

        $this->counter2 = BusinessCounter::updateOrCreate(
            ['business_id' => $this->business->id, 'code' => 'L-02'],
            [
                'name' => 'Kursi 2',
                'is_active' => true,
            ]
        );

        $this->operator = User::factory()->merchant()->create([
            'name' => 'Operator Station Test',
            'email' => 'operator.station@example.com',
        ]);

        $opMembership = BusinessMembership::create([
            'business_id' => $this->business->id,
            'organization_id' => $this->business->organization_id,
            'user_id' => $this->operator->id,
            'role' => BusinessMembership::ROLE_OPERATOR,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->counter1->assignedMemberships()->sync([$opMembership->id]);
        $this->counter2->assignedMemberships()->sync([$opMembership->id]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Potong Rambut',
            'description' => 'Potong rambut rapi dan styling.',
            'duration_minutes' => 15,
            'price' => 45000,
            'daily_quota' => 40,
            'is_active' => true,
        ]);
    }

    public function test_owner_can_toggle_station_mode_and_downstream_services_respect_single_station(): void
    {
        $bookingSlots = app(BookingSlotService::class);
        $displayService = app(DisplayService::class);
        $waitService = app(WaitingTimeEstimationService::class);

        // Multi-station default (2 active counters, 15 min service => 4 * 2 = 8 per 60m slot)
        $this->assertTrue($this->business->usesStations());
        $this->assertSame(2, $this->business->effectiveStationCount());
        $this->assertSame(8, $bookingSlots->calculateSlotCapacity($this->business, $this->service));
        $multiDisplay = $displayService->formatDisplayData($this->business);
        $this->assertCount(2, $multiDisplay['counters']);
        $this->assertTrue($multiDisplay['meta']['use_stations']);

        // Operator cannot toggle station mode
        $this->actingAs($this->operator)
            ->patch(route('merchant.counters.toggle-station-mode'), ['use_stations' => '0'])
            ->assertForbidden();

        // Owner toggles to 1 station mode
        $this->actingAs($this->owner)
            ->patch(route('merchant.counters.toggle-station-mode'), ['use_stations' => '0'])
            ->assertRedirect(route('merchant.counters.index'));

        $this->business->refresh();
        $this->assertFalse($this->business->usesStations());
        $this->assertSame(1, $this->business->effectiveStationCount());

        // Even when eager-loaded active_counters_count = 2, effectiveStationCount stays 1
        $loadedBusiness = Business::withCount(['activeCounters as active_counters_count'])->findOrFail($this->business->id);
        $this->assertSame(2, (int) $loadedBusiness->getAttributes()['active_counters_count']);
        $this->assertSame(1, $loadedBusiness->effectiveStationCount());

        // Slot capacity scales down to 1 station (4 per 60m slot)
        $this->assertSame(4, $bookingSlots->calculateSlotCapacity($this->business, $this->service));

        // DisplayService caps counters to 1 primary station
        $singleDisplay = $displayService->formatDisplayData($this->business);
        $this->assertCount(1, $singleDisplay['counters']);
        $this->assertFalse($singleDisplay['meta']['use_stations']);

        // Wait range estimation calculates for 1 station (4 waiting ahead * 15m = ~60m, vs ~30m on 2 stations)
        $dailyQueue = DailyQueue::create([
            'business_id' => $this->business->id,
            'queue_date' => Carbon::today('Asia/Makassar')->toDateString(),
            'current_number' => 5,
            'status' => DailyQueue::STATUS_ACTIVE,
        ]);

        $tickets = [];
        for ($i = 1; $i <= 5; $i++) {
            $tickets[] = QueueTicket::create([
                'business_id' => $this->business->id,
                'daily_queue_id' => $dailyQueue->id,
                'service_id' => $this->service->id,
                'ticket_number' => sprintf('TKT-%03d', $i),
                'sequence_number' => $i,
                'customer_name' => 'Pelanggan '.$i,
                'source' => QueueTicket::SOURCE_ONLINE,
                'status' => QueueTicket::STATUS_MENUNGGU,
                'access_token' => str_repeat((string) $i, 40),
            ]);
        }

        $singleEstimate = $waitService->estimateScheduleForTicket($tickets[4], 5);
        $this->assertSame(60, $singleEstimate['estimated_wait_minutes']);

        // Toggle back to multi-station => estimate halves to 30m
        $this->actingAs($this->owner)
            ->patch(route('merchant.counters.toggle-station-mode'), ['use_stations' => '1'])
            ->assertRedirect(route('merchant.counters.index'));

        $this->business->refresh();
        $tickets[4]->unsetRelation('business');
        $multiEstimate = $waitService->estimateScheduleForTicket($tickets[4], 5);
        $this->assertSame(30, $multiEstimate['estimated_wait_minutes']);
    }

    public function test_merchant_views_reflect_station_mode_dynamic_display_grid_and_gradient_cards(): void
    {
        // Multi-station (2 counters) => display preview uses 2-col grid and dashboard shows switcher
        $this->actingAs($this->operator)
            ->get(route('merchant.display.index'))
            ->assertOk()
            ->assertSee('2 Loket Aktif')
            ->assertSee('sm:grid-cols-2');

        $this->actingAs($this->operator)
            ->get(route('merchant.dashboard'))
            ->assertOk()
            ->assertSee('header_counter_select')
            ->assertSee('data-stat-tile data-tone="warning"', false)
            ->assertSee('data-now-serving', false);

        // Switch to 1 Station mode
        $this->business->update(['use_stations' => false]);
        $owner = $this->owner->fresh();
        $operator = $this->operator->fresh();

        $this->actingAs($owner)
            ->get(route('merchant.counters.index'))
            ->assertOk()
            ->assertSee('Mode 1 Loket Utama')
            ->assertSee('Kelola Loket Pelayanan')
            ->assertSee('data-stat-tile data-tone="success"', false);

        $this->actingAs($owner)
            ->get(route('merchant.services.index'))
            ->assertOk()
            ->assertSee('data-stat-tile data-tone="success"', false);

        $this->actingAs($operator)
            ->get(route('merchant.display.index'))
            ->assertOk()
            ->assertSee('1 Loket Utama')
            ->assertSee('max-w-md mx-auto');

        $this->actingAs($operator)
            ->get(route('merchant.dashboard'))
            ->assertOk()
            ->assertSee('1 Loket Utama')
            ->assertDontSee('id="header_counter_select"', false);

        $this->actingAs($operator)
            ->get(route('merchant.queues'))
            ->assertOk()
            ->assertSee('1 Loket Utama')
            ->assertDontSee('id="queue_counter_filter"', false);
    }

    public function test_hd_brand_logo_and_dark_blue_customer_summary_cards_render_cleanly(): void
    {
        $this->assertFileExists(public_path('images/antrein-logo.svg'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('images/antrein-logo.svg')
            ->assertSee('rel="icon"', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('images/antrein-logo.svg');

        $this->get(route('customer.queue', ['slug' => $this->business->slug]))
            ->assertOk()
            ->assertSee('images/antrein-logo.svg')
            ->assertSee('Menunggu')
            ->assertSee('Perkiraan tunggu')
            ->assertSee('Layanan tersedia')
            ->assertDontSee('from-brand-900 via-blue-900 to-indigo-950');
    }

    public function test_expanded_development_seeder_populates_many_services_reviews_and_scaling_controls(): void
    {
        $this->seed([
            BusinessCategorySeeder::class,
            AdminUserSeeder::class,
            DevelopmentMerchantSeeder::class,
        ]);

        $garasi = Business::where('slug', 'barbershop-garasi-demo')->firstOrFail();
        $kopi = Business::where('slug', 'kopi-senja-roastery')->firstOrFail();
        $nusantara = Business::where('slug', 'pangkas-rambut-nusantara')->firstOrFail();
        $customer = User::where('email', 'pelanggan@antrein.id')->firstOrFail();

        $this->assertGreaterThanOrEqual(12, $garasi->services()->count());
        $this->assertGreaterThanOrEqual(15, $garasi->customerReviews()->count());

        $this->assertGreaterThanOrEqual(12, $kopi->services()->count());
        $this->assertSame(3, $kopi->counters()->count());
        $this->assertGreaterThanOrEqual(24, $kopi->customerReviews()->count());

        $this->assertFalse($nusantara->usesStations());

        $activeTicketsCount = QueueTicket::where('user_id', $customer->id)
            ->whereIn('status', [
                QueueTicket::STATUS_MENUNGGU,
                QueueTicket::STATUS_DIPANGGIL,
                QueueTicket::STATUS_SEDANG_DILAYANI,
            ])
            ->count();
        $this->assertLessThan(3, $activeTicketsCount);

        // Customer store detail collapses after 6 services with toggle button
        $this->get(route('customer.queue', ['slug' => $garasi->slug]))
            ->assertOk()
            ->assertSee('Tampilkan semua ('.$garasi->services()->count().') layanan');

        // Customer queue form includes service search bar when > 5 services
        $this->actingAs($customer)
            ->get(route('customer.queue.create', ['slug' => $garasi->slug]))
            ->assertOk()
            ->assertSee('placeholder="Cari layanan..."', false);
    }
}

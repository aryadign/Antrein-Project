<?php

namespace Tests\Feature\Customer;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreOpeningHoursStatusTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::firstOrCreate(['slug' => 'barbershop'], ['name' => 'Barbershop']);

        $this->business = Business::create([
            'user_id' => User::factory()->merchant()->create()->id,
            'category_id' => $category->id,
            'name' => 'Barbershop Jam Buka',
            'slug' => 'barbershop-jam-buka',
            'address' => 'Jl. Teuku Umar No. 1, Denpasar',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
            'is_suspended' => false,
        ]);

        foreach (range(0, 6) as $day) {
            BusinessHour::create([
                'business_id' => $this->business->id,
                'day_of_week' => $day,
                'open_time' => '08:00',
                'close_time' => '21:30',
                'is_closed' => $day === 0,
            ]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_store_switched_on_is_open_only_inside_todays_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 12:00', 'Asia/Makassar'));
        $this->assertTrue($this->business->fresh()->isOpenNow());
        $this->assertSame('Buka s/d 21.30', $this->business->fresh()->customerHoursBadge()['label']);

        Carbon::setTestNow(Carbon::parse('2026-11-20 22:05', 'Asia/Makassar'));
        $this->assertTrue($this->business->fresh()->isOpen(), 'The merchant status switch is unchanged.');
        $this->assertFalse($this->business->fresh()->isOpenNow());
        $this->assertSame(['label' => 'Buka besok 08.00', 'tone' => 'closed'], $this->business->fresh()->customerHoursBadge());

        Carbon::setTestNow(Carbon::parse('2026-11-20 06:30', 'Asia/Makassar'));
        $this->assertSame('Buka pukul 08.00', $this->business->fresh()->nextOpeningLabel());
    }

    public function test_next_opening_skips_closed_days(): void
    {
        // Saturday night: Sunday is closed, so the next opening is Monday.
        Carbon::setTestNow(Carbon::parse('2026-11-21 22:05', 'Asia/Makassar'));

        $this->assertSame('Buka Senin 08.00', $this->business->fresh()->nextOpeningLabel());
    }

    public function test_customer_store_page_shows_closed_after_hours_instead_of_open(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 22:05', 'Asia/Makassar'));

        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Buka besok 08.00')
            ->assertDontSee('Buka s/d 21.30');
    }
}

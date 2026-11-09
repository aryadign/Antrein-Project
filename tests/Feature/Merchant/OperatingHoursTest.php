<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatingHoursTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Bengkel',
            'slug' => 'bengkel',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Bengkel Motor Kilat',
            'slug' => 'bengkel-motor-kilat',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        Service::create([
            'business_id' => $this->business->id,
            'name' => 'Tune Up',
            'duration_minutes' => 60,
            'price' => 100000,
            'is_active' => true,
        ]);
    }

    public function test_merchant_can_update_weekly_operating_hours(): void
    {
        $hoursData = [];
        for ($i = 0; $i < 7; $i++) {
            $hoursData[] = [
                'day_of_week' => $i,
                'open_time' => '08:30',
                'close_time' => '17:30',
                'is_closed' => ($i === 0) ? '1' : '0', // Closed on Sunday
            ];
        }

        $response = $this->actingAs($this->merchant)->put(route('merchant.settings.hours'), [
            'hours' => $hoursData,
        ]);

        $response->assertRedirect(route('merchant.settings', ['tab' => 'hours']));

        $this->assertDatabaseCount('business_hours', 7);

        // Verify Sunday is closed
        $this->assertDatabaseHas('business_hours', [
            'business_id' => $this->business->id,
            'day_of_week' => 0,
            'is_closed' => true,
        ]);

        // Verify Monday is open with configured times
        $this->assertDatabaseHas('business_hours', [
            'business_id' => $this->business->id,
            'day_of_week' => 1,
            'open_time' => '08:30',
            'close_time' => '17:30',
            'is_closed' => false,
        ]);
    }

    public function test_open_day_requires_close_time_after_open_time(): void
    {
        $hoursData = [];
        for ($i = 0; $i < 7; $i++) {
            $hoursData[] = [
                'day_of_week' => $i,
                'open_time' => ($i === 1) ? '18:00' : '09:00',
                'close_time' => ($i === 1) ? '09:00' : '17:00', // Invalid: close before open
                'is_closed' => '0',
            ];
        }

        $response = $this->actingAs($this->merchant)->put(route('merchant.settings.hours'), [
            'hours' => $hoursData,
        ]);

        $response->assertSessionHasErrors(['hours.1.close_time']);
    }

    public function test_closed_days_do_not_require_opening_or_closing_times(): void
    {
        $hoursData = [];
        for ($i = 0; $i < 7; $i++) {
            $hoursData[] = [
                'day_of_week' => $i,
                'open_time' => null,
                'close_time' => null,
                'is_closed' => '1', // All closed
            ];
        }

        $response = $this->actingAs($this->merchant)->put(route('merchant.settings.hours'), [
            'hours' => $hoursData,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('business_hours', 7);
    }
}

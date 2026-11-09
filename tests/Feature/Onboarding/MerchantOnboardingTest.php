<?php

namespace Tests\Feature\Onboarding;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private BusinessCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = BusinessCategory::create([
            'name' => 'Barbershop & Salon',
            'slug' => 'barbershop-salon',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_onboarding(): void
    {
        $response = $this->get(route('merchant.onboarding'));

        $response->assertRedirect(route('login'));
    }

    public function test_unonboarded_merchant_is_redirected_to_onboarding_when_accessing_dashboard(): void
    {
        $merchant = User::factory()->merchant()->create();

        $response = $this->actingAs($merchant)->get(route('merchant.dashboard'));

        $response->assertRedirect(route('merchant.onboarding'));
    }

    public function test_merchant_can_complete_step_1_business_profile_with_unique_slug(): void
    {
        $merchant = User::factory()->merchant()->create();

        $response = $this->actingAs($merchant)->post(route('merchant.onboarding.step1'), [
            'name' => 'Barbershop Garasi',
            'category_id' => $this->category->id,
            'description' => 'Barbershop modern di Denpasar',
            'address' => 'Jl. Gatot Subroto No. 45, Denpasar',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
        ]);

        $response->assertRedirect(route('merchant.onboarding', ['step' => 2]));

        $this->assertDatabaseHas('businesses', [
            'user_id' => $merchant->id,
            'name' => 'Barbershop Garasi',
            'slug' => 'barbershop-garasi',
            'timezone' => 'Asia/Makassar',
        ]);

        // Second merchant with the same name gets an auto-incremented slug
        $merchant2 = User::factory()->merchant()->create();
        $this->actingAs($merchant2)->post(route('merchant.onboarding.step1'), [
            'name' => 'Barbershop Garasi',
            'category_id' => $this->category->id,
            'address' => 'Jl. Lain',
            'phone' => '081234567899',
            'timezone' => 'Asia/Makassar',
        ]);

        $this->assertDatabaseHas('businesses', [
            'user_id' => $merchant2->id,
            'slug' => 'barbershop-garasi-2',
        ]);
    }

    public function test_merchant_can_complete_step_2_operating_hours(): void
    {
        $merchant = User::factory()->merchant()->create();

        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $this->category->id,
            'name' => 'Garasi Barbershop',
            'slug' => 'garasi-barbershop',
            'address' => 'Jl. Kuta',
            'phone' => '0811111111',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $hoursData = [];
        for ($i = 0; $i < 7; $i++) {
            $hoursData[] = [
                'day_of_week' => $i,
                'open_time' => '09:00',
                'close_time' => '21:00',
                'is_closed' => ($i === 0) ? '1' : '0',
            ];
        }

        $response = $this->actingAs($merchant)->post(route('merchant.onboarding.step2'), [
            'hours' => $hoursData,
        ]);

        $response->assertRedirect(route('merchant.onboarding', ['step' => 3]));
        $this->assertDatabaseCount('business_hours', 7);
        $this->assertDatabaseHas('business_hours', [
            'business_id' => $business->id,
            'day_of_week' => 0,
            'is_closed' => true,
        ]);
    }

    public function test_merchant_can_complete_step_3_initial_service(): void
    {
        $merchant = User::factory()->merchant()->create();

        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $this->category->id,
            'name' => 'Garasi Barbershop',
            'slug' => 'garasi-barbershop',
            'address' => 'Jl. Kuta',
            'phone' => '0811111111',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $response = $this->actingAs($merchant)->post(route('merchant.onboarding.step3'), [
            'name' => 'Potong Rambut Gentleman',
            'duration_minutes' => 45,
            'price' => 50000,
            'daily_quota' => 25,
        ]);

        $response->assertRedirect(route('merchant.onboarding', ['step' => 4]));

        $this->assertDatabaseHas('services', [
            'business_id' => $business->id,
            'name' => 'Potong Rambut Gentleman',
            'duration_minutes' => 45,
            'price' => 50000,
            'daily_quota' => 25,
            'is_active' => true,
        ]);
    }

    public function test_merchant_can_view_step_4_and_download_qr_code(): void
    {
        $merchant = User::factory()->merchant()->create();

        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $this->category->id,
            'name' => 'Garasi Barbershop',
            'slug' => 'garasi-barbershop',
            'address' => 'Jl. Kuta',
            'phone' => '0811111111',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        Service::create([
            'business_id' => $business->id,
            'name' => 'Cukur',
            'duration_minutes' => 30,
            'price' => 35000,
            'is_active' => true,
        ]);

        // View step 4
        $viewResponse = $this->actingAs($merchant)->get(route('merchant.onboarding', ['step' => 4]));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Onboarding Siap Selesai!');
        $viewResponse->assertSee('garasi-barbershop');

        // Download QR code SVG
        $downloadResponse = $this->actingAs($merchant)->get(route('merchant.onboarding.download-qr'));
        $downloadResponse->assertOk();
        $downloadResponse->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg xmlns="http://www.w3.org/2000/svg"', $downloadResponse->getContent());
    }

    public function test_completing_onboarding_unlocks_merchant_dashboard(): void
    {
        $merchant = User::factory()->merchant()->create();

        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $this->category->id,
            'name' => 'Garasi Barbershop',
            'slug' => 'garasi-barbershop',
            'address' => 'Jl. Kuta',
            'phone' => '0811111111',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        Service::create([
            'business_id' => $business->id,
            'name' => 'Cukur',
            'duration_minutes' => 30,
            'price' => 35000,
            'is_active' => true,
        ]);

        $completeResponse = $this->actingAs($merchant)->post(route('merchant.onboarding.complete'));
        $completeResponse->assertRedirect(route('merchant.dashboard'));

        // Dashboard is now fully accessible
        $dashResponse = $this->actingAs($merchant)->get(route('merchant.dashboard'));
        $dashResponse->assertOk();
    }
}


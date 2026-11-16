<?php

namespace Tests\Feature\Onboarding;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $viewResponse->assertSee('Pendaftaran UMKM Siap Dikirim!');
        $viewResponse->assertSee('<svg', false);

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

    public function test_step_1_accepts_optional_logo_upload(): void
    {
        Storage::fake('public');
        $merchant = User::factory()->merchant()->create();

        $response = $this->actingAs($merchant)->post(route('merchant.onboarding.step1'), [
            'name' => 'Kopi Senja',
            'category_id' => $this->category->id,
            'address' => 'Jl. Teuku Umar No. 10, Denpasar',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
        ]);

        $response->assertRedirect(route('merchant.onboarding', ['step' => 2]));

        $business = Business::where('user_id', $merchant->id)->firstOrFail();
        $this->assertNotNull($business->logo_path);
        $this->assertStringStartsWith('logos/', $business->logo_path);
        Storage::disk('public')->assertExists($business->logo_path);

        $this->actingAs($merchant)->get(route('merchant.onboarding', ['step' => 1]))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('Ganti Logo');
    }

    public function test_step_1_rejects_non_image_logo(): void
    {
        Storage::fake('public');
        $merchant = User::factory()->merchant()->create();

        $response = $this->actingAs($merchant)->post(route('merchant.onboarding.step1'), [
            'name' => 'Kopi Senja',
            'category_id' => $this->category->id,
            'address' => 'Jl. Teuku Umar No. 10, Denpasar',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('logo');
        $this->assertDatabaseMissing('businesses', ['user_id' => $merchant->id]);
    }

    public function test_step_3_accepts_optional_service_photo(): void
    {
        Storage::fake('public');
        $merchant = User::factory()->merchant()->create();
        $business = $this->createBusinessFor($merchant);

        $response = $this->actingAs($merchant)->post(route('merchant.onboarding.step3'), [
            'name' => 'Potong Rambut Gentleman',
            'duration_minutes' => 45,
            'price' => 50000,
            'image' => UploadedFile::fake()->image('cukur.jpg', 600, 400),
        ]);

        $response->assertRedirect(route('merchant.onboarding', ['step' => 4]));

        $service = Service::where('business_id', $business->id)->firstOrFail();
        $this->assertStringStartsWith('services/'.$business->id.'/', $service->image_path);
        Storage::disk('public')->assertExists($service->image_path);
    }

    public function test_step_3_offers_skip_when_services_already_exist(): void
    {
        $merchant = User::factory()->merchant()->create();
        $business = $this->createBusinessFor($merchant);
        $this->createServiceFor($business);

        $this->actingAs($merchant)->get(route('merchant.onboarding', ['step' => 3]))
            ->assertOk()
            ->assertSee('1 layanan sudah terdaftar')
            ->assertSee('Lewati, Lanjut ke Langkah 4')
            ->assertSee('Tambah Layanan Ini');
    }

    public function test_step_4_is_reachable_without_hours_and_shows_reminder(): void
    {
        $merchant = User::factory()->merchant()->create();
        $business = $this->createBusinessFor($merchant);
        $this->createServiceFor($business);

        $this->actingAs($merchant)->get(route('merchant.onboarding', ['step' => 4]))
            ->assertOk()
            ->assertSee('Pendaftaran UMKM Siap Dikirim!')
            ->assertSee('Jam standar akan dipasang otomatis')
            ->assertSee('Verifikasi Admin AntreIn')
            ->assertSee('Unduh QR Code (SVG)')
            ->assertSee('Kirim Pendaftaran untuk Verifikasi Admin', false)
            ->assertDontSee('Salin Tautan')
            ->assertDontSee('&bull;', false)
            ->assertDontSee('·');
    }

    public function test_step_4_hides_reminder_when_hours_are_configured(): void
    {
        $merchant = User::factory()->merchant()->create();
        $business = $this->createBusinessFor($merchant);
        $this->createServiceFor($business);

        BusinessHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '08:00',
            'close_time' => '17:00',
            'is_closed' => false,
        ]);

        $this->actingAs($merchant)->get(route('merchant.onboarding', ['step' => 4]))
            ->assertOk()
            ->assertDontSee('Jam operasional belum diatur');
    }

    public function test_completing_onboarding_without_hours_applies_default_schedule(): void
    {
        $merchant = User::factory()->merchant()->create();
        $business = $this->createBusinessFor($merchant);
        $this->createServiceFor($business);

        $this->actingAs($merchant)->post(route('merchant.onboarding.complete'))
            ->assertRedirect(route('merchant.dashboard'));

        $hours = $business->hours()->orderBy('day_of_week')->get();
        $this->assertCount(7, $hours);

        $sunday = $hours->firstWhere('day_of_week', 0);
        $this->assertTrue((bool) $sunday->is_closed);

        foreach ($hours->where('day_of_week', '!=', 0) as $hour) {
            $this->assertFalse((bool) $hour->is_closed);
            $this->assertSame('09:00', substr($hour->open_time, 0, 5));
            $this->assertSame('21:00', substr($hour->close_time, 0, 5));
        }
    }

    public function test_completing_onboarding_keeps_existing_hours(): void
    {
        $merchant = User::factory()->merchant()->create();
        $business = $this->createBusinessFor($merchant);
        $this->createServiceFor($business);

        BusinessHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '08:00',
            'close_time' => '17:00',
            'is_closed' => false,
        ]);

        $this->actingAs($merchant)->post(route('merchant.onboarding.complete'));

        $this->assertSame(1, $business->hours()->count());
        $this->assertSame('08:00', substr($business->hours()->first()->open_time, 0, 5));
    }

    private function createBusinessFor(User $merchant): Business
    {
        return Business::create([
            'user_id' => $merchant->id,
            'category_id' => $this->category->id,
            'name' => 'Garasi Barbershop',
            'slug' => 'garasi-barbershop',
            'address' => 'Jl. Kuta',
            'phone' => '0811111111',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);
    }

    private function createServiceFor(Business $business): Service
    {
        return Service::create([
            'business_id' => $business->id,
            'name' => 'Cukur',
            'duration_minutes' => 30,
            'price' => 35000,
            'is_active' => true,
        ]);
    }
}

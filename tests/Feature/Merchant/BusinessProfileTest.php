<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;
    private BusinessCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = BusinessCategory::create([
            'name' => 'Barbershop & Salon',
            'slug' => 'barbershop-salon',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create();

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $this->category->id,
            'name' => 'Garasi Barbershop',
            'slug' => 'garasi-barbershop',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        Service::create([
            'business_id' => $this->business->id,
            'name' => 'Cukur Rambut',
            'duration_minutes' => 30,
            'price' => 45000,
            'is_active' => true,
        ]);
    }

    public function test_merchant_can_view_own_business_profile_settings(): void
    {
        $response = $this->actingAs($this->merchant)->get(route('merchant.settings', ['tab' => 'profile']));

        $response->assertOk();
        $response->assertSee('Garasi Barbershop');
        $response->assertSee('garasi-barbershop');
        $response->assertSee('Asia/Makassar');
    }

    public function test_merchant_can_update_business_profile(): void
    {
        $response = $this->actingAs($this->merchant)->put(route('merchant.settings.profile'), [
            'name' => 'Garasi Barbershop Premium',
            'category_id' => $this->category->id,
            'slug' => 'garasi-premium',
            'phone' => '081999888777',
            'timezone' => 'Asia/Jakarta',
            'description' => 'Barbershop pria eksklusif',
            'address' => 'Jl. Diponegoro No. 12',
        ]);

        $response->assertRedirect(route('merchant.settings', ['tab' => 'profile']));

        $this->assertDatabaseHas('businesses', [
            'id' => $this->business->id,
            'name' => 'Garasi Barbershop Premium',
            'slug' => 'garasi-premium',
            'phone' => '081999888777',
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    public function test_duplicate_slug_taken_by_another_business_is_rejected(): void
    {
        $otherOwner = User::factory()->merchant()->create();
        Business::create([
            'user_id' => $otherOwner->id,
            'category_id' => $this->category->id,
            'name' => 'Toko Lain',
            'slug' => 'toko-lain',
            'phone' => '08111111111',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $response = $this->actingAs($this->merchant)->put(route('merchant.settings.profile'), [
            'name' => 'Garasi Barbershop',
            'category_id' => $this->category->id,
            'slug' => 'toko-lain', // Collision
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
        ]);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_logo_upload_and_replacement_works_safely(): void
    {
        Storage::fake('public');

        $initialImage = UploadedFile::fake()->image('logo1.png', 400, 400);

        // Upload first logo
        $response1 = $this->actingAs($this->merchant)->put(route('merchant.settings.profile'), [
            'name' => $this->business->name,
            'category_id' => $this->category->id,
            'slug' => $this->business->slug,
            'phone' => $this->business->phone,
            'timezone' => $this->business->timezone,
            'logo' => $initialImage,
        ]);

        $response1->assertRedirect();
        $firstPath = $this->business->fresh()->logo_path;
        $this->assertNotNull($firstPath);
        Storage::disk('public')->assertExists($firstPath);

        // Replace with second logo
        $secondImage = UploadedFile::fake()->image('logo2.webp', 400, 400);
        $response2 = $this->actingAs($this->merchant)->put(route('merchant.settings.profile'), [
            'name' => $this->business->name,
            'category_id' => $this->category->id,
            'slug' => $this->business->slug,
            'phone' => $this->business->phone,
            'timezone' => $this->business->timezone,
            'logo' => $secondImage,
        ]);

        $response2->assertRedirect();
        $secondPath = $this->business->fresh()->logo_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertExists($secondPath);
        // Old file must be cleaned up
        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_invalid_logo_file_is_rejected(): void
    {
        Storage::fake('public');

        $badFile = UploadedFile::fake()->create('malicious.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->merchant)->put(route('merchant.settings.profile'), [
            'name' => $this->business->name,
            'category_id' => $this->category->id,
            'slug' => $this->business->slug,
            'phone' => $this->business->phone,
            'timezone' => $this->business->timezone,
            'logo' => $badFile,
        ]);

        $response->assertSessionHasErrors(['logo']);
        $this->assertNull($this->business->fresh()->logo_path);
    }

    public function test_merchant_can_remove_existing_logo(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('logo.jpg', 300, 300);
        $path = $image->store('logos', 'public');
        $this->business->update(['logo_path' => $path]);

        $response = $this->actingAs($this->merchant)->delete(route('merchant.settings.logo.remove'));

        $response->assertRedirect();
        $this->assertNull($this->business->fresh()->logo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_guest_is_redirected_when_accessing_merchant_settings(): void
    {
        $response = $this->get(route('merchant.settings'));

        $response->assertRedirect(route('login'));
    }
}

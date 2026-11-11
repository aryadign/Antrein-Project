<?php

namespace Tests\Feature\Authorization;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'owns.business'])->get('/test-tenant/{business}', function (Business $business) {
            return response()->json(['business_id' => $business->id]);
        });
    }

    public function test_merchant_can_access_their_own_business(): void
    {
        $category = BusinessCategory::create(['name' => 'Klinik', 'slug' => 'klinik', 'is_active' => true]);

        $owner = User::factory()->merchant()->create();
        $myBusiness = Business::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Klinik Sehat',
            'slug' => 'klinik-sehat',
            'address' => 'Jl. Kesehatan 1',
            'phone' => '0811111111',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $response = $this->actingAs($owner)->get('/test-tenant/'.$myBusiness->id);

        $response->assertOk();
        $response->assertJson(['business_id' => $myBusiness->id]);
    }

    public function test_merchant_cannot_access_another_merchants_business(): void
    {
        $category = BusinessCategory::create(['name' => 'Klinik', 'slug' => 'klinik', 'is_active' => true]);

        $owner1 = User::factory()->merchant()->create();
        $business1 = Business::create([
            'user_id' => $owner1->id,
            'category_id' => $category->id,
            'name' => 'Klinik Satu',
            'slug' => 'klinik-satu',
            'address' => 'Jl. Satu',
            'phone' => '0811111111',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $owner2 = User::factory()->merchant()->create();
        $business2 = Business::create([
            'user_id' => $owner2->id,
            'category_id' => $category->id,
            'name' => 'Klinik Dua',
            'slug' => 'klinik-dua',
            'address' => 'Jl. Dua',
            'phone' => '0822222222',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        // Owner 2 tries to access Business 1
        $response = $this->actingAs($owner2)->get('/test-tenant/'.$business1->id);

        $response->assertForbidden();
    }

    public function test_admin_has_cross_tenant_access(): void
    {
        $category = BusinessCategory::create(['name' => 'Klinik', 'slug' => 'klinik', 'is_active' => true]);

        $owner = User::factory()->merchant()->create();
        $business = Business::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Klinik Tiga',
            'slug' => 'klinik-tiga',
            'address' => 'Jl. Tiga',
            'phone' => '0833333333',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/test-tenant/'.$business->id);

        $response->assertOk();
        $response->assertJson(['business_id' => $business->id]);
    }
}


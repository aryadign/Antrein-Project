<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk ke Akun Anda');
    }

    public function test_merchant_without_onboarding_redirects_to_onboarding_after_login(): void
    {
        $merchant = User::factory()->merchant()->create([
            'email' => 'newowner@garasi.id',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'newowner@garasi.id',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($merchant);
        $response->assertRedirect(route('merchant.onboarding'));
    }

    public function test_merchant_with_onboarding_redirects_to_dashboard_after_login(): void
    {
        $merchant = User::factory()->merchant()->create([
            'email' => 'onboarded@garasi.id',
            'password' => bcrypt('password123'),
        ]);

        $category = BusinessCategory::create([
            'name' => 'Barbershop & Salon',
            'slug' => 'barbershop-salon',
            'is_active' => true,
        ]);

        $business = Business::create([
            'user_id' => $merchant->id,
            'category_id' => $category->id,
            'name' => 'Barbershop Garasi',
            'slug' => 'barbershop-garasi',
            'address' => 'Jl. Merdeka No. 10',
            'phone' => '08123456789',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        Service::create([
            'business_id' => $business->id,
            'name' => 'Potong Rambut',
            'duration_minutes' => 30,
            'price' => 45000,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'onboarded@garasi.id',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($merchant);
        $response->assertRedirect(route('merchant.dashboard'));
    }

    public function test_admin_redirects_to_admin_dashboard_after_login(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@antrein.id',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@antrein.id',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_inactive_user_cannot_login_and_gets_deactivation_message(): void
    {
        $inactiveMerchant = User::factory()->merchant()->inactive()->create([
            'email' => 'banned@garasi.id',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'banned@garasi.id',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.',
        ]);
    }

    public function test_login_rate_limiting_triggers_after_5_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'target@antrein.id',
            'password' => bcrypt('correct-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'target@antrein.id',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => 'target@antrein.id',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }
}


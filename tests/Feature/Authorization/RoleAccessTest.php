<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_redirected_to_login_when_accessing_admin(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_redirected_to_login_when_accessing_merchant(): void
    {
        $response = $this->get('/umkm/onboarding');

        $response->assertRedirect(route('login'));
    }

    public function test_merchant_cannot_access_admin_dashboard_returns_403(): void
    {
        $merchant = User::factory()->merchant()->create();

        $response = $this->actingAs($merchant)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_cannot_access_merchant_onboarding_returns_403(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/umkm/onboarding');

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
    }

    public function test_public_admin_registration_route_does_not_exist(): void
    {
        $response = $this->get('/admin/register');
        $response->assertNotFound();

        $postResponse = $this->post('/admin/register', [
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => 'secret123',
        ]);
        $postResponse->assertNotFound();
    }
}


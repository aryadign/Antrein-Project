<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Daftarkan Usaha Anda');
    }

    public function test_new_merchant_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@garasi.id',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('merchant.onboarding'));

        $user = User::where('email', 'budi@garasi.id')->first();
        $this->assertNotNull($user);
        $this->assertSame('Budi Santoso', $user->name);
        $this->assertSame(User::ROLE_MERCHANT, $user->role);
        $this->assertTrue($user->is_active);

        // Activity log verification
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'register',
        ]);
    }

    public function test_registration_strictly_forces_merchant_role_preventing_admin_elevation(): void
    {
        // Malicious user attempts to register with role=admin
        $response = $this->post('/register', [
            'name' => 'Attacker',
            'email' => 'attacker@antrein.id',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'attacker@antrein.id')->first();
        $this->assertNotNull($user);

        // Role MUST remain merchant
        $this->assertSame(User::ROLE_MERCHANT, $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_registration_fails_with_existing_email(): void
    {
        User::factory()->create([
            'email' => 'existing@usaha.id',
        ]);

        $response = $this->post('/register', [
            'name' => 'Duplicate User',
            'email' => 'existing@usaha.id',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_registration_fails_with_unconfirmed_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi',
            'email' => 'budi@test.id',
            'password' => 'Password123!',
            'password_confirmation' => 'MismatchedPassword',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }
}


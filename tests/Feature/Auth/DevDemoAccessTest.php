<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use App\Services\DevDemoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevDemoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders_developer_quick_access_panel_in_local_or_testing_environments(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Akses Cepat Pengujian (Dev Mode)');
        $response->assertSee('Admin AntreIn');
        $response->assertSee('Owner UMKM');
        $response->assertSee('Operator Loket 1');
        $response->assertSee('Pelanggan (Tamu)');
        $response->assertSee('admin@antrein.id');
        $response->assertSee('owner@garasi.id');
        $response->assertSee('operator@garasi.id');
    }

    public function test_login_screen_does_not_render_developer_quick_access_panel_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('Akses Cepat Pengujian (Dev Mode)');
        $response->assertDontSee('LOCAL / TEST ONLY');
    }

    public function test_quick_login_can_authenticate_as_admin(): void
    {
        $response = $this->post(route('dev.quick-login'), [
            'persona' => 'admin',
        ]);

        $this->assertAuthenticated();
        /** @var User $user */
        $user = auth()->user();
        $this->assertTrue($user->isAdmin());
        $this->assertSame(DevDemoService::ADMIN_EMAIL, $user->email);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_demo_can_logout_cleanly(): void
    {
        $this->post(route('dev.quick-login'), ['persona' => 'admin']);
        $this->assertAuthenticated();

        $previousSessionId = session()->getId();
        $previousToken = session()->token();

        $dashboard = $this->get(route('admin.dashboard'));
        $dashboard->assertStatus(200);
        $dashboard->assertSee('action="' . route('logout') . '"', false);
        $dashboard->assertSee('href="' . route('admin.settings') . '"', false);
        $dashboard->assertDontSee('href="#logout"', false);
        $dashboard->assertDontSee('href="#settings"', false);

        $response = $this->post(route('logout'));
        $this->assertGuest();
        $response->assertRedirect(route('home'));

        $this->assertNotSame($previousSessionId, session()->getId());
        $this->assertNotSame($previousToken, session()->token());

        // Subsequent homepage render is guest-facing
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertDontSee('Dashboard Admin');
        $homeResponse->assertSee('Masuk');

        // Subsequent protected admin route access is blocked
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_owner_and_operator_demo_can_logout_without_losing_demo_data(): void
    {
        foreach (['owner', 'operator'] as $persona) {
            $this->post(route('dev.quick-login'), ['persona' => $persona]);
            $this->assertAuthenticated();

            $dashboard = $this->get(route('merchant.dashboard'));
            $dashboard->assertStatus(200);
            $dashboard->assertSee('action="' . route('logout') . '"', false);
            $dashboard->assertDontSee('href="#logout"', false);

            $logoutResponse = $this->post(route('logout'));
            $this->assertGuest();
            $logoutResponse->assertRedirect(route('home'));

            $this->get(route('merchant.dashboard'))
                ->assertRedirect(route('login'));
        }

        $business = Business::where('slug', DevDemoService::DEMO_SLUG)->firstOrFail();
        $this->assertSame(1, BusinessMembership::where('business_id', $business->id)->where('role', 'owner')->count());
        $this->assertSame(1, BusinessMembership::where('business_id', $business->id)->where('role', 'operator')->count());
        $this->assertGreaterThanOrEqual(1, $business->counters()->count());
    }

    public function test_quick_login_can_authenticate_as_owner_and_redirects_to_merchant_dashboard(): void
    {
        $response = $this->post(route('dev.quick-login'), [
            'persona' => 'owner',
        ]);

        $this->assertAuthenticated();
        /** @var User $user */
        $user = auth()->user();
        $this->assertTrue($user->isOwner());
        $this->assertSame(DevDemoService::OWNER_EMAIL, $user->email);

        $response->assertRedirect(route('merchant.dashboard'));
    }

    public function test_owner_demo_membership_is_verified_and_can_access_owner_routes(): void
    {
        $this->post(route('dev.quick-login'), ['persona' => 'owner']);
        $this->assertAuthenticated();

        // Owner can access owner-only routes like counters index
        $response = $this->get(route('merchant.counters.index'));
        $response->assertStatus(200);
    }

    public function test_quick_login_can_authenticate_as_operator_and_redirects_to_merchant_dashboard(): void
    {
        $response = $this->post(route('dev.quick-login'), [
            'persona' => 'operator',
        ]);

        $this->assertAuthenticated();
        /** @var User $user */
        $user = auth()->user();
        $this->assertTrue($user->isOperator());
        $this->assertSame(DevDemoService::OPERATOR_EMAIL, $user->email);

        $response->assertRedirect(route('merchant.dashboard'));
    }

    public function test_operator_demo_is_forbidden_from_owner_only_routes(): void
    {
        $this->post(route('dev.quick-login'), ['persona' => 'operator']);
        $this->assertAuthenticated();

        // Operator attempting to access owner-only counter management should get 403 Forbidden
        $response = $this->get(route('merchant.counters.index'));
        $response->assertStatus(403);
    }

    public function test_quick_login_as_customer_logs_out_session_and_redirects_to_guest_queue(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('dev.quick-login'), [
            'persona' => 'customer',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('customer.queue', ['slug' => DevDemoService::DEMO_SLUG]));
    }

    public function test_customer_guest_can_access_barbershop_public_queue_with_services(): void
    {
        $this->post(route('dev.quick-login'), ['persona' => 'customer']);

        $response = $this->get(route('customer.queue', ['slug' => DevDemoService::DEMO_SLUG]));
        $response->assertStatus(200);
        $response->assertSee('Barbershop Garasi');
        $response->assertSee('Potong Rambut Dewasa');
    }

    public function test_switch_persona_changes_authenticated_user_from_owner_to_admin(): void
    {
        /** @var DevDemoService $devDemo */
        $devDemo = app(DevDemoService::class);
        $owner = $devDemo->ensureOwnerUser();

        $response = $this->actingAs($owner)->post(route('dev.switch-persona'), [
            'persona' => 'admin',
        ]);

        $this->assertAuthenticated();
        /** @var User $user */
        $user = auth()->user();
        $this->assertTrue($user->isAdmin());
        $this->assertSame(DevDemoService::ADMIN_EMAIL, $user->email);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_switch_persona_changes_authenticated_user_from_admin_to_operator(): void
    {
        /** @var DevDemoService $devDemo */
        $devDemo = app(DevDemoService::class);
        $admin = $devDemo->ensureAdminUser();

        $response = $this->actingAs($admin)->post(route('dev.switch-persona'), [
            'persona' => 'operator',
        ]);

        $this->assertAuthenticated();
        /** @var User $user */
        $user = auth()->user();
        $this->assertTrue($user->isOperator());
        $this->assertSame(DevDemoService::OPERATOR_EMAIL, $user->email);

        $response->assertRedirect(route('merchant.dashboard'));
    }

    public function test_switch_persona_to_customer_clears_session_and_redirects_to_guest_queue(): void
    {
        /** @var DevDemoService $devDemo */
        $devDemo = app(DevDemoService::class);
        $operator = $devDemo->ensureOperatorUser();

        $response = $this->actingAs($operator)->post(route('dev.switch-persona'), [
            'persona' => 'customer',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('customer.queue', ['slug' => DevDemoService::DEMO_SLUG]));
    }

    public function test_invalid_persona_is_rejected_with_validation_error(): void
    {
        $response = $this->post(route('dev.quick-login'), [
            'persona' => 'arbitrary_role_injection',
        ]);

        $response->assertSessionHasErrors('persona');
        $this->assertGuest();
    }

    public function test_arbitrary_user_id_parameter_is_ignored(): void
    {
        $targetUser = User::factory()->create([
            'email' => 'victim@example.com',
        ]);

        // Attempt to pass arbitrary user_id parameter along with admin persona
        $response = $this->post(route('dev.quick-login'), [
            'persona' => 'admin',
            'user_id' => $targetUser->id,
            'id' => $targetUser->id,
        ]);

        $this->assertAuthenticated();
        /** @var User $user */
        $user = auth()->user();
        $this->assertSame(DevDemoService::ADMIN_EMAIL, $user->email);
        $this->assertNotSame($targetUser->id, $user->id);
    }

    public function test_dev_demo_access_routes_abort_404_in_production(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->app->detectEnvironment(fn () => 'production');

        $this->post('/dev/quick-login', ['persona' => 'admin'])
            ->assertStatus(404);

        $this->post('/dev/switch-persona', ['persona' => 'admin'])
            ->assertStatus(404);
    }

    public function test_demo_account_setup_is_idempotent_and_does_not_duplicate_records(): void
    {
        /** @var DevDemoService $devDemo */
        $devDemo = app(DevDemoService::class);

        // Call resolution multiple times
        $devDemo->ensureAdminUser();
        $devDemo->ensureAdminUser();

        $devDemo->ensureOwnerUser();
        $devDemo->ensureOwnerUser();

        $devDemo->ensureOperatorUser();
        $devDemo->ensureOperatorUser();

        $this->assertSame(1, User::where('email', DevDemoService::ADMIN_EMAIL)->count());
        $this->assertSame(1, User::where('email', DevDemoService::OWNER_EMAIL)->count());
        $this->assertSame(1, User::where('email', DevDemoService::OPERATOR_EMAIL)->count());
        $this->assertSame(1, Business::where('slug', DevDemoService::DEMO_SLUG)->count());
    }

    public function test_authenticated_merchant_hitting_login_redirects_to_merchant_dashboard(): void
    {
        /** @var DevDemoService $devDemo */
        $devDemo = app(DevDemoService::class);
        $owner = $devDemo->ensureOwnerUser();

        $response = $this->actingAs($owner)->get('/login');

        $response->assertRedirect(route('merchant.dashboard'));
    }

    public function test_authenticated_admin_hitting_login_redirects_to_admin_dashboard(): void
    {
        /** @var DevDemoService $devDemo */
        $devDemo = app(DevDemoService::class);
        $admin = $devDemo->ensureAdminUser();

        $response = $this->actingAs($admin)->get('/login');

        $response->assertRedirect(route('admin.dashboard'));
    }
}

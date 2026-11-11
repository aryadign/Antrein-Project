<?php

namespace Tests\Feature\Home;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_can_be_rendered_with_success_status(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertSee('AntreIn');
    }

    public function test_homepage_renders_official_branding_and_tagline(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Antrean Lebih Teratur, Pelayanan Lebih Lancar.');
        $response->assertSee('Kelola antrean online dan walk-in dalam satu platform digital yang membantu UMKM melayani pelanggan dengan lebih mudah.');
    }

    public function test_homepage_renders_all_mandatory_sections_and_features(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Cara Kerja');
        $response->assertSee('Fitur');
        $response->assertSee('Untuk UMKM');
        $response->assertSee('Pertanyaan yang Sering Diajukan (FAQ)');
        $response->assertSee('A-001'); // Mockup queue card number
        $response->assertSee('Unified Online &amp; Walk-in Queue', false);
        $response->assertSee('Multi-Counter Management');
        $response->assertSee('Real-Time Queue Updates');
        $response->assertSee('Email &amp; Browser Notifications', false);
        $response->assertSee('Reports &amp; Analytics', false);
        $response->assertSee('CSV &amp; PDF Export', false);
        $response->assertSee('Public Queue Display');
        $response->assertSee('Customer Reviews');
        $response->assertSee('Historical Waiting Time Estimation');
    }

    public function test_homepage_does_not_render_internal_design_system_testbed(): void
    {
        $response = $this->get('/');

        // Design system testbed has this specific title/heading
        $response->assertDontSee('Design System & UI Preview Testbed');
        $response->assertDontSee('Halaman ini berfungsi sebagai showcase');
    }

    public function test_homepage_shows_guest_navigation_buttons(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Masuk');
        $response->assertSee('Daftarkan UMKM');
        $response->assertDontSee('Dashboard Admin');
        $response->assertDontSee('Dashboard UMKM');
    }

    public function test_homepage_shows_merchant_dashboard_button_when_logged_in_as_merchant(): void
    {
        $merchant = User::factory()->merchant()->create();

        $response = $this->actingAs($merchant)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dashboard UMKM');
        $response->assertDontSee('Dashboard Admin');
    }

    public function test_homepage_shows_admin_dashboard_button_when_logged_in_as_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Admin');
        $response->assertDontSee('Dashboard UMKM');
    }

    public function test_preview_routes_are_accessible_in_local_or_testing_environments(): void
    {
        $previewRoutes = [
            '/design-system',
            '/preview/design-system',
            '/preview/admin',
            '/preview/merchant',
            '/preview/customer',
            '/preview/auth',
        ];

        foreach ($previewRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }
    }

    public function test_preview_routes_abort_404_in_production_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $previewRoutes = [
            '/design-system',
            '/preview/design-system',
            '/preview/admin',
            '/preview/merchant',
            '/preview/customer',
            '/preview/auth',
        ];

        foreach ($previewRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(404);
        }
    }
}

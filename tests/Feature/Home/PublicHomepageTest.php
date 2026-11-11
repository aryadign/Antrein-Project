<?php

namespace Tests\Feature\Home;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Service;
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

    public function test_homepage_renders_branding_and_hero_sections(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Kelola Antrean Usaha Anda');
        $response->assertSee('Cara Kerja');
        $response->assertSee('Fitur UMKM');
        $response->assertSee('Akses Pelanggan');
        $response->assertSee('Pertanyaan yang Sering Diajukan (FAQ)');
        $response->assertSee('Daftarkan Usaha');
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
        $response->assertSee('Daftarkan Usaha');
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

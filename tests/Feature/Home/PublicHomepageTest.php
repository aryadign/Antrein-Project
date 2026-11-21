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
        $response->assertSee('Satu antrean untuk pelanggan yang mendaftar dari ponsel dan yang datang langsung, sehingga UMKM melayani dengan urutan yang adil.');
    }

    public function test_homepage_renders_all_mandatory_sections_and_features(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Cara Kerja');
        $response->assertSee('Fitur');
        $response->assertSee('Untuk UMKM');
        $response->assertSee('Pertanyaan yang sering diajukan');
        $response->assertSee('TKT-001'); // Mockup queue card number
        $response->assertSee('Satu antrean, urutan yang adil');
        $response->assertSee('Satu antrean untuk pelanggan dari ponsel dan yang datang langsung');
        $response->assertSee('Banyak loket, banyak staf, dan banyak cabang');
        $response->assertSee('Posisi antrean dan estimasi tunggu diperbarui otomatis');
        $response->assertSee('Pemberitahuan lewat email dan peramban saat giliran dekat');
        $response->assertSee('Laporan harian yang bisa diunduh sebagai CSV atau PDF');
        $response->assertSee('Layar antrean untuk TV atau monitor');
        $response->assertSee('Ulasan dan rating setelah dilayani');
        $response->assertSee('Estimasi waktu tunggu dari riwayat pelayanan');
    }

    public function test_homepage_copy_is_indonesian_without_developer_jargon_or_unbuilt_features(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        foreach (['Unified Online', 'Multi-Counter Management', 'Reports &amp; Analytics', 'Customer Reviews', 'Laravel Reverb', 'WebSocket', 'RBAC', 'First-In First-Out', 'Text-to-Speech', 'Take-Away', 'Pilih stylist', 'walk-in', '(FAQ)', 'Dashboard UMKM', 'Dashboard Admin'] as $phrase) {
            $response->assertDontSee($phrase, false);
        }
    }

    public function test_homepage_hides_signup_links_from_signed_in_users(): void
    {
        $this->get('/')->assertSee('href="'.route('register', ['type' => 'merchant']).'"', false);

        $merchant = User::factory()->merchant()->create();

        $this->actingAs($merchant)->get('/')
            ->assertOk()
            ->assertDontSee('href="'.route('register', ['type' => 'merchant']).'"', false);
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
        $response->assertDontSee('Dasbor Admin');
        $response->assertDontSee('Dasbor UMKM');
    }

    public function test_homepage_shows_merchant_dashboard_button_when_logged_in_as_merchant(): void
    {
        $merchant = User::factory()->merchant()->create();

        $response = $this->actingAs($merchant)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dasbor UMKM');
        $response->assertDontSee('Dasbor Admin');
    }

    public function test_homepage_shows_admin_dashboard_button_when_logged_in_as_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dasbor Admin');
        $response->assertDontSee('Dasbor UMKM');
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

    public function test_homepage_offers_customer_login_and_registration_entry(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('href="'.route('login').'"', false);
        $response->assertSee('Daftar Pelanggan');
        $response->assertSee('Cari Toko');
        $response->assertSee('href="'.route('customer.dashboard').'"', false);
        $response->assertSee('Saya pelanggan');
        $response->assertSee('Saya pemilik usaha');
    }

    public function test_homepage_has_no_decorative_dots_or_bullet_separators(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('&bull;', false);
        $response->assertDontSee('•');
        $response->assertDontSee('·');
        $response->assertDontSee('animate-ping', false);
        $response->assertSee('Buka s/d 21.00');
    }

    public function test_login_page_supports_customers_and_merchants(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Untuk Pelanggan, Pemilik UMKM, Staf Operator, dan Admin AntreIn.');
        $response->assertSee('Ingin mengambil nomor antrean?');
        $response->assertSee('Daftar Pelanggan');
        $response->assertSee('href="'.route('customer.dashboard').'"', false);
        $response->assertDontSee('•');
    }

    public function test_register_page_offers_customer_and_umkm_tabs_with_password_toggle(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('Pelanggan');
        $response->assertSee('Pemilik UMKM');
        $response->assertSee('Tampilkan kata sandi');
        $response->assertSee('href="'.route('login').'"', false);
    }

    public function test_homepage_renders_gojek_inspired_showcase_assets_and_category_cards(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('images/landing/hero.jpg')
            ->assertSee('images/landing/app-ticket.jpg')
            ->assertSee('images/landing/app-merchant.jpg')
            ->assertSee('images/landing/app-display.jpg')
            ->assertSee('Cocok untuk berbagai jenis usaha');
    }

    public function test_customer_layout_brand_logo_links_guests_back_to_landing_page(): void
    {
        $guestResponse = $this->get(route('customer.dashboard'));
        $guestResponse->assertOk()
            ->assertSee('href="'.route('home').'"', false);

        $customer = User::factory()->customer()->create();
        $customerResponse = $this->actingAs($customer)->get(route('customer.dashboard'));
        $customerResponse->assertOk()
            ->assertSee('href="'.route('customer.dashboard').'"', false);
    }

    public function test_homepage_uses_antrein_brand_blue_and_white_palette(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('bg-brand-800', false)
            ->assertSee('bg-white', false)
            ->assertDontSee('#0B132B', false)
            ->assertDontSee('#060B19', false);
    }
}

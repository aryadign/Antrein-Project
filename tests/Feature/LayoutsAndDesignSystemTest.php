<?php

namespace Tests\Feature;

use Tests\TestCase;

class LayoutsAndDesignSystemTest extends TestCase
{
    public function test_design_system_showcase_renders_successfully(): void
    {
        $response = $this->get('/design-system');

        $response->assertStatus(200)
            ->assertSee('Design System Testbed')
            ->assertSee('Menunggu')
            ->assertSee('Dipanggil')
            ->assertSee('Sedang dilayani')
            ->assertSee('Selesai')
            ->assertSee('Dilewati')
            ->assertSee('Dibatalkan')
            ->assertSee('Primary Button')
            ->assertSee('A-015')
            ->assertSee('tabular-nums');
    }

    public function test_admin_layout_preview_renders_with_dark_sidebar(): void
    {
        $response = $this->get('/preview/admin');

        $response->assertStatus(200)
            ->assertSee('AntreIn')
            ->assertSee('Admin')
            ->assertSee('Platform Governance & Monitoring')
            ->assertSee('Dashboard')
            ->assertSee('Manajemen UMKM')
            ->assertSee('Manajemen Pengguna')
            ->assertSee('Monitoring Antrean')
            ->assertSee('Kategori UMKM')
            ->assertSee('Pengaturan Platform')
            ->assertSee('Barbershop Garasi')
            ->assertSee('WITA (Asia/Makassar)');
    }

    public function test_merchant_layout_preview_renders_with_now_serving_and_status(): void
    {
        $response = $this->get('/preview/merchant');

        $response->assertStatus(200)
            ->assertSee('AntreIn')
            ->assertSee('UMKM')
            ->assertSee('Barbershop Garasi')
            ->assertSee('Status Operasional:')
            ->assertSee('Sedang Berlangsung (Now Serving)')
            ->assertSee('A-014')
            ->assertSee('Daftar Tunggu Antrean (FIFO)')
            ->assertSee('Tambah Antrean Walk-in')
            ->assertSee('Selesaikan Pelayanan');
    }

    public function test_customer_layout_preview_renders_responsive_web_interface(): void
    {
        $response = $this->get('/preview/customer');

        $response->assertStatus(200)
            ->assertSee('AntreIn')
            ->assertSee('Barbershop Garasi')
            ->assertSee('Layanan & Pendaftaran Antrean', false)
            ->assertSee('Potong Rambut Dewasa')
            ->assertSee('Rp 45.000')
            ->assertSee('Ambil Antrean')
            ->assertSee('Nomor Antrean Anda')
            ->assertSee('A-018')
            ->assertSee('Powered by AntreIn');
    }

    public function test_auth_layout_preview_renders_successfully(): void
    {
        $response = $this->get('/preview/auth');

        $response->assertStatus(200)
            ->assertSee('AntreIn')
            ->assertSee('Masuk ke Akun Anda')
            ->assertSee('Alamat Email')
            ->assertSee('Kata Sandi')
            ->assertSee('Ingat saya di perangkat ini')
            ->assertSee('Masuk ke Akun');
    }
}

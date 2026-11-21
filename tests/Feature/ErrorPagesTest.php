<?php

namespace Tests\Feature;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_404_page_renders_custom_view(): void
    {
        $response = $this->get('/non-existent-endpoint-xyz');

        $response->assertStatus(404)
            ->assertSee('Kode kesalahan 404')
            ->assertSee('Halaman Tidak Ditemukan')
            ->assertSee('Kembali ke Beranda');
    }

    public function test_other_error_views_render_in_bahasa_with_a_recovery_action(): void
    {
        $cases = [
            403 => ['Akses Ditolak', 'Masuk ke Akun'],
            419 => ['Sesi Halaman Berakhir', 'Muat Ulang Halaman'],
            429 => ['Terlalu Banyak Percobaan', 'Coba Lagi'],
            500 => ['Terjadi Kesalahan Server', 'Coba Lagi'],
            503 => ['Sedang dalam Pemeliharaan', 'Muat Ulang Halaman'],
        ];

        foreach ($cases as $code => [$title, $action]) {
            $html = view("errors.{$code}", ['exception' => new HttpException($code)])->render();

            $this->assertStringContainsString($title, $html);
            $this->assertStringContainsString($action, $html);
            $this->assertStringContainsString("Kode kesalahan {$code}", $html);
        }
    }

    public function test_403_view_shows_the_specific_reason_when_one_is_given(): void
    {
        $html = view('errors.403', ['exception' => new HttpException(403, 'Anda tidak memiliki hak akses untuk mengubah layanan ini.')])->render();

        $this->assertStringContainsString('Anda tidak memiliki hak akses untuk mengubah layanan ini.', $html);
    }
}

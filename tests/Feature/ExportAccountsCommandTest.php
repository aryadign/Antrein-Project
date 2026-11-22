<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExportAccountsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_accounts_with_only_verified_seeder_passwords(): void
    {
        User::factory()->customer()->create(['name' => 'Sinta Dewi', 'email' => 'sinta@contoh.id', 'password' => Hash::make('pelanggan12345')]);
        User::factory()->customer()->create(['name' => 'Bayu Aji', 'email' => 'bayu@contoh.id', 'password' => Hash::make('rahasia-baru')]);

        $path = storage_path('framework/testing/akun-test.csv');

        $this->artisan('accounts:export', ['path' => $path])->assertSuccessful();

        $csv = file_get_contents($path);
        unlink($path);

        $this->assertStringContainsString('Pelanggan;"Sinta Dewi";sinta@contoh.id;pelanggan12345', $csv);
        $this->assertStringContainsString('Pelanggan;"Bayu Aji";bayu@contoh.id;"(sudah diganti pengguna)"', $csv);
        $this->assertStringNotContainsString('rahasia-baru', $csv);
    }
}

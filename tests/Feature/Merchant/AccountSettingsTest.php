<?php

namespace Tests\Feature\Merchant;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::create([
            'name' => 'Kuliner & Kafe',
            'slug' => 'kuliner-kafe',
            'is_active' => true,
        ]);

        $this->merchant = User::factory()->merchant()->create([
            'name' => 'I Wayan Pengusaha',
            'email' => 'wayan@warungbalienak.id',
            'password' => Hash::make('PasswordLama123!'),
        ]);

        $this->business = Business::create([
            'user_id' => $this->merchant->id,
            'category_id' => $category->id,
            'name' => 'Warung Bali Enak',
            'slug' => 'warung-bali-enak',
            'phone' => '081234567890',
            'timezone' => 'Asia/Makassar',
            'status' => Business::STATUS_BUKA,
        ]);

        Service::create([
            'business_id' => $this->business->id,
            'name' => 'Menu Nasi Campur Spesial',
            'duration_minutes' => 15,
            'price' => 35000,
            'is_active' => true,
        ]);
    }

    public function test_merchant_can_view_account_settings_tab(): void
    {
        $response = $this->actingAs($this->merchant)->get(route('merchant.settings', ['tab' => 'account']));

        $response->assertOk();
        $response->assertSee('Pengaturan Akun Pengguna');
        $response->assertSee('I Wayan Pengusaha');
        $response->assertSee('wayan@warungbalienak.id');
        $response->assertSee('Ubah Password');
        $response->assertSee('Web Polling Real-time (In-App)');
    }

    public function test_merchant_can_update_name_and_email(): void
    {
        $response = $this->actingAs($this->merchant)->put(route('merchant.settings.account'), [
            'name' => 'I Wayan Pengusaha Baru',
            'email' => 'wayan.baru@warungbalienak.id',
        ]);

        $response->assertRedirect(route('merchant.settings', ['tab' => 'account']));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $this->merchant->id,
            'name' => 'I Wayan Pengusaha Baru',
            'email' => 'wayan.baru@warungbalienak.id',
        ]);
    }

    public function test_merchant_can_update_password_with_correct_current_password(): void
    {
        $response = $this->actingAs($this->merchant)->put(route('merchant.settings.account'), [
            'name' => 'I Wayan Pengusaha',
            'email' => 'wayan@warungbalienak.id',
            'current_password' => 'PasswordLama123!',
            'password' => 'PasswordBaru456!',
            'password_confirmation' => 'PasswordBaru456!',
        ]);

        $response->assertRedirect(route('merchant.settings', ['tab' => 'account']));
        $response->assertSessionHas('status');

        $this->assertTrue(Hash::check('PasswordBaru456!', $this->merchant->fresh()->password));
    }

    public function test_password_update_fails_when_current_password_is_incorrect(): void
    {
        $response = $this->actingAs($this->merchant)->put(route('merchant.settings.account'), [
            'name' => 'I Wayan Pengusaha',
            'email' => 'wayan@warungbalienak.id',
            'current_password' => 'SalahPassword!',
            'password' => 'PasswordBaru456!',
            'password_confirmation' => 'PasswordBaru456!',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('PasswordLama123!', $this->merchant->fresh()->password));
    }

    public function test_email_update_fails_if_email_is_taken_by_another_user(): void
    {
        User::factory()->create(['email' => 'sudahada@antrein.id']);

        $response = $this->actingAs($this->merchant)->put(route('merchant.settings.account'), [
            'name' => 'I Wayan Pengusaha',
            'email' => 'sudahada@antrein.id',
        ]);

        $response->assertSessionHasErrors('email');
    }
}

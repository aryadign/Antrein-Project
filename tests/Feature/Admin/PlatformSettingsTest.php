<?php

namespace Tests\Feature\Admin;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_merchant_cannot_access_platform_settings(): void
    {
        $response = $this->get(route('admin.settings'));
        $response->assertRedirect(route('login'));

        $merchant = User::factory()->merchant()->create();
        $response = $this->actingAs($merchant)->get(route('admin.settings'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_settings_form(): void
    {
        $admin = User::factory()->admin()->create();

        PlatformSetting::set('platform_name', 'AntreIn Bali');
        PlatformSetting::set('contact_email', 'admin@antrein.bali');

        $response = $this->actingAs($admin)->get(route('admin.settings'));

        $response->assertOk();
        $response->assertViewIs('admin.settings.index');
        $response->assertSee('AntreIn Bali');
        $response->assertSee('admin@antrein.bali');
    }

    public function test_admin_can_update_platform_settings_successfully(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'platform_name' => 'AntreIn Nusantara',
            'platform_tagline' => 'Digitalisasi Antrean UMKM Seluruh Indonesia',
            'contact_email' => 'halo@antrein.id',
            'contact_phone' => '081122334455',
            'default_timezone' => 'Asia/Jakarta',
        ]);

        $response->assertRedirect(route('admin.settings'));
        $response->assertSessionHas('status');

        $this->assertEquals('AntreIn Nusantara', PlatformSetting::get('platform_name'));
        $this->assertEquals('Digitalisasi Antrean UMKM Seluruh Indonesia', PlatformSetting::get('platform_tagline'));
        $this->assertEquals('halo@antrein.id', PlatformSetting::get('contact_email'));
        $this->assertEquals('081122334455', PlatformSetting::get('contact_phone'));
        $this->assertEquals('Asia/Jakarta', PlatformSetting::get('default_timezone'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'platform_settings_updated',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_upload_new_platform_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $logo = UploadedFile::fake()->image('logo.png', 400, 400);

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'platform_name' => 'AntreIn Official',
            'platform_tagline' => 'Platform Resmi',
            'contact_email' => 'admin@antrein.id',
            'contact_phone' => '081234567890',
            'default_timezone' => 'Asia/Makassar',
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('admin.settings'));

        $savedPath = PlatformSetting::get('platform_logo_path');
        $this->assertNotNull($savedPath);
        Storage::disk('public')->assertExists($savedPath);
    }

    public function test_invalid_settings_payload_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'platform_name' => '', // required
            'contact_email' => 'not-an-email', // invalid email
            'contact_phone' => '', // required
            'default_timezone' => 'Invalid/Timezone', // invalid timezone
        ]);

        $response->assertSessionHasErrors(['platform_name', 'contact_email', 'contact_phone', 'default_timezone']);
    }
}


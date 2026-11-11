<?php

namespace Tests\Feature\Security;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::factory()->admin()->create();

        PlatformSetting::set('platform_name', 'AntreIn Test');
        PlatformSetting::set('platform_tagline', 'Solusi Antrean');
        PlatformSetting::set('contact_email', 'contact@antrein.id');
        PlatformSetting::set('contact_phone', '081234567890');
        PlatformSetting::set('default_timezone', 'Asia/Makassar');
    }

    public function test_malicious_svg_with_script_is_rejected(): void
    {
        $maliciousSvg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert("XSS")</script><circle cx="50" cy="50" r="40"/></svg>';
        $file = UploadedFile::fake()->createWithContent('malicious.svg', $maliciousSvg);

        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'platform_name' => 'AntreIn Test',
            'contact_email' => 'contact@antrein.id',
            'contact_phone' => '081234567890',
            'default_timezone' => 'Asia/Makassar',
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors('logo');
        $this->assertNull(PlatformSetting::get('platform_logo_path'));
    }

    public function test_malicious_svg_with_event_handler_is_rejected(): void
    {
        $maliciousSvg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><circle cx="50" cy="50" r="40"/></svg>';
        $file = UploadedFile::fake()->createWithContent('malicious_event.svg', $maliciousSvg);

        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'platform_name' => 'AntreIn Test',
            'contact_email' => 'contact@antrein.id',
            'contact_phone' => '081234567890',
            'default_timezone' => 'Asia/Makassar',
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors('logo');
        $this->assertNull(PlatformSetting::get('platform_logo_path'));
    }

    public function test_valid_image_upload_is_stored_safely(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 200, 200)->size(500);

        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'platform_name' => 'AntreIn Test',
            'contact_email' => 'contact@antrein.id',
            'contact_phone' => '081234567890',
            'default_timezone' => 'Asia/Makassar',
            'logo' => $file,
        ]);

        $response->assertRedirect(route('admin.settings'));
        $this->assertNotNull(PlatformSetting::get('platform_logo_path'));
    }

    public function test_oversized_file_is_rejected(): void
    {
        // 3000 KB exceeds 2048 KB limit
        $file = UploadedFile::fake()->image('huge.png', 200, 200)->size(3000);

        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'platform_name' => 'AntreIn Test',
            'contact_email' => 'contact@antrein.id',
            'contact_phone' => '081234567890',
            'default_timezone' => 'Asia/Makassar',
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors('logo');
    }
}

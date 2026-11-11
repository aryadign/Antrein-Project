<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlatformSettingController extends Controller
{
    /**
     * Display the platform configuration and identity settings.
     */
    public function show(): View
    {
        $settings = [
            'platform_name' => PlatformSetting::get('platform_name', 'AntreIn'),
            'platform_tagline' => PlatformSetting::get('platform_tagline', 'Solusi Antrean Digital UMKM Terpadu'),
            'contact_email' => PlatformSetting::get('contact_email', 'support@antrein.id'),
            'contact_phone' => PlatformSetting::get('contact_phone', '+62 812-3456-7890'),
            'default_timezone' => PlatformSetting::get('default_timezone', 'Asia/Makassar'),
            'logo_url' => PlatformSetting::getLogoUrl(),
        ];

        return view('admin.settings.index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update platform identity and configurations.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:100'],
            'platform_tagline' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['required', 'string', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'default_timezone' => ['required', 'string', 'in:Asia/Makassar,Asia/Jakarta,Asia/Jayapura'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,webp,svg', 'max:2048'],
        ]);

        $oldLogoPath = PlatformSetting::get('platform_logo_path');
        $newLogoPath = null;

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $mime = (string) $file->getMimeType();

            // Strict SVG Security Sanitization (M10-06)
            if ($extension === 'svg' || str_contains($mime, 'svg')) {
                $content = (string) file_get_contents($file->getRealPath());
                if (preg_match('/<\s*(script|iframe|foreignobject|object|embed)/i', $content) ||
                    preg_match('/(on\w+\s*=|javascript\s*:|data\s*:)/i', $content)) {
                    return back()->withErrors([
                        'logo' => 'File SVG tidak aman. Format SVG dilarang mengandung script atau atribut interaktif.',
                    ])->withInput();
                }
            }

            $safeName = 'platform-logo-' . Str::random(24) . '.' . $extension;

            $newLogoPath = $file->storeAs('platform', $safeName, 'public');

            // Delete previous logo after successful storage
            if ($oldLogoPath && Storage::disk('public')->exists($oldLogoPath)) {
                Storage::disk('public')->delete($oldLogoPath);
            }

            PlatformSetting::set('platform_logo_path', $newLogoPath);
        }

        PlatformSetting::set('platform_name', $validated['platform_name']);
        PlatformSetting::set('platform_tagline', $validated['platform_tagline'] ?? '');
        PlatformSetting::set('contact_email', $validated['contact_email']);
        PlatformSetting::set('contact_phone', $validated['contact_phone']);
        PlatformSetting::set('default_timezone', $validated['default_timezone']);

        ActivityLog::log(
            action: 'platform_settings_updated',
            description: 'Konfigurasi identitas platform AntreIn diperbarui oleh administrator.',
            business: null,
            user: $request->user(),
            properties: [
                'platform_name' => $validated['platform_name'],
                'has_new_logo' => ! empty($newLogoPath),
            ]
        );

        return redirect()->route('admin.settings')
            ->with('status', 'Pengaturan identitas platform berhasil disimpan.');
    }
}


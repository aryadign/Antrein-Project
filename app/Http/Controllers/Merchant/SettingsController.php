<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the merchant business profile and operating settings.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $business = $user->business;

        $categories = BusinessCategory::query()
            ->where('is_active', true)
            ->orWhere('id', $business?->category_id)
            ->orderBy('name')
            ->get();

        $hours = $business
            ? $business->hours()->orderBy('day_of_week')->get()
            : collect();

        $activeTab = $request->query('tab', 'profile');

        return view('merchant.settings.index', [
            'business' => $business,
            'categories' => $categories,
            'hours' => $hours,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Update business profile information and logo.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:business_categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:30'],
            'timezone' => ['required', 'string', 'in:Asia/Makassar,Asia/Jakarta,Asia/Jayapura'],
            'slug' => [
                'required',
                'string',
                'max:180',
                'alpha_dash',
                Rule::unique('businesses', 'slug')->ignore($business->id),
            ],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $oldLogo = $business->logo_path;
        $newLogoPath = null;

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $safeName = 'logo-'.$business->id.'-'.Str::random(24).'.'.$extension;

            $newLogoPath = $file->storeAs('logos', $safeName, 'public');
        }

        try {
            DB::transaction(function () use ($business, $validated, $newLogoPath) {
                $payload = [
                    'name' => $validated['name'],
                    'category_id' => $validated['category_id'],
                    'description' => $validated['description'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'phone' => $validated['phone'],
                    'timezone' => $validated['timezone'],
                    'slug' => Str::slug($validated['slug']),
                ];

                if ($newLogoPath !== null) {
                    $payload['logo_path'] = $newLogoPath;
                }

                $business->update($payload);

                ActivityLog::log(
                    action: 'business_profile_updated',
                    description: 'Profil dan informasi usaha berhasil diperbarui',
                    business: $business,
                    user: $business->user
                );
            });

            // Clean up old logo only after successful database commit
            if ($newLogoPath !== null && $oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
        } catch (\Throwable $e) {
            // If database transaction failed and new file was uploaded, safely purge orphaned new file
            if ($newLogoPath !== null && Storage::disk('public')->exists($newLogoPath)) {
                Storage::disk('public')->delete($newLogoPath);
            }

            throw $e;
        }

        return redirect()->route('merchant.settings', ['tab' => 'profile'])
            ->with('status', 'Profil usaha berhasil diperbarui!');
    }

    /**
     * Remove existing business logo.
     */
    public function removeLogo(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($business && $business->logo_path) {
            $oldPath = $business->logo_path;
            $business->update(['logo_path' => null]);

            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            ActivityLog::log(
                action: 'business_logo_removed',
                description: 'Logo usaha berhasil dihapus',
                business: $business,
                user: $user
            );
        }

        return redirect()->route('merchant.settings', ['tab' => 'profile'])
            ->with('status', 'Logo usaha berhasil dihapus.');
    }

    /**
     * Update weekly operating hours.
     */
    public function updateHours(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding');
        }

        $validated = $request->validate([
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'hours.*.open_time' => ['nullable', 'date_format:H:i'],
            'hours.*.close_time' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ]);

        // Validate time requirements for open days
        foreach ($validated['hours'] as $idx => $day) {
            $isClosed = ! empty($day['is_closed']);
            if (! $isClosed) {
                if (empty($day['open_time']) || empty($day['close_time'])) {
                    return back()->withErrors([
                        "hours.{$idx}.open_time" => 'Jam buka dan tutup wajib diisi untuk hari operasional yang aktif.',
                    ])->withInput();
                }

                if ($day['open_time'] >= $day['close_time']) {
                    return back()->withErrors([
                        "hours.{$idx}.close_time" => 'Jam tutup harus lebih besar daripada jam buka (jadwal lintas hari belum didukung di MVP).',
                    ])->withInput();
                }
            }
        }

        DB::transaction(function () use ($business, $validated, $user) {
            foreach ($validated['hours'] as $dayData) {
                $isClosed = ! empty($dayData['is_closed']);

                BusinessHour::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'day_of_week' => $dayData['day_of_week'],
                    ],
                    [
                        'open_time' => $isClosed ? '09:00' : $dayData['open_time'],
                        'close_time' => $isClosed ? '21:00' : $dayData['close_time'],
                        'is_closed' => $isClosed,
                    ]
                );
            }

            ActivityLog::log(
                action: 'operating_hours_updated',
                description: 'Jadwal operasional mingguan berhasil diperbarui',
                business: $business,
                user: $user
            );
        });

        return redirect()->route('merchant.settings', ['tab' => 'hours'])
            ->with('status', 'Jadwal jam operasional mingguan berhasil disimpan!');
    }

    /**
     * Update manual business operational status (Buka / Jeda / Tutup).
     */
    public function updateStatus(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding');
        }

        if ($business->is_suspended) {
            return back()->withErrors([
                'status' => 'Akun usaha Anda sedang dalam masa suspensi oleh administrator platform. Perubahan status operasional tidak diizinkan.',
            ]);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:buka,jeda,tutup'],
        ]);

        $previousStatus = $business->status;
        $newStatus = $validated['status'];

        $business->update(['status' => $newStatus]);

        ActivityLog::log(
            action: 'operational_status_changed',
            description: sprintf('Status operasional usaha diubah dari %s menjadi %s', strtoupper($previousStatus), strtoupper($newStatus)),
            business: $business,
            user: $user,
            properties: ['from' => $previousStatus, 'to' => $newStatus]
        );

        $statusLabels = [
            Business::STATUS_BUKA => 'BUKA (Menerima antrean pelanggan)',
            Business::STATUS_JEDA => 'JEDA SEMENTARA (Antrean online baru ditahan, tiket berjalan tetap dilayani)',
            Business::STATUS_TUTUP => 'TUTUP (Operasional antrean ditutup)',
        ];

        return redirect()->route('merchant.settings', ['tab' => 'status'])
            ->with('status', sprintf('Status operasional usaha berhasil diubah menjadi %s.', $statusLabels[$newStatus] ?? $newStatus));
    }
}

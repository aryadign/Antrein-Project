<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Services\DisplayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisplayManagementController extends Controller
{
    public function __construct(
        protected DisplayService $displayService
    ) {}

    /**
     * Display the merchant's TV/monitor public queue display management screen.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        /** @var Business|null $business */
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding');
        }

        $displayData = $this->displayService->formatDisplayData($business);
        $activeCountersCount = $business->activeCounters()->count();

        return view('merchant.display.index', [
            'business' => $business,
            'displayData' => $displayData,
            'activeCountersCount' => $activeCountersCount,
        ]);
    }

    /**
     * Toggle the public queue display status (enabled or disabled).
     */
    public function toggle(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var Business|null $business */
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding');
        }

        $request->validate([
            'is_display_enabled' => ['required', 'boolean'],
        ]);

        $enabled = $request->boolean('is_display_enabled');
        $rawToken = null;

        if ($enabled && empty($business->display_token_hash)) {
            $rawToken = $business->generateDisplayToken();
            session()->flash('one_time_display_token', $rawToken);
        } else {
            $business->update(['is_display_enabled' => $enabled]);
        }

        ActivityLog::log(
            action: 'display_status_toggled',
            description: sprintf('Layar antrean publik %s', $enabled ? 'diaktifkan' : 'dinonaktifkan'),
            business: $business,
            user: $user
        );

        $message = $enabled
            ? 'Layar antrean publik berhasil diaktifkan.'
            : 'Layar antrean publik berhasil dinonaktifkan.';

        return redirect()->route('merchant.display.index')->with('status', $message);
    }

    /**
     * Generate or rotate the public display token (invalidates previous link).
     */
    public function generate(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var Business|null $business */
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding');
        }

        $isRegeneration = ! empty($business->display_token_hash);
        $rawToken = $business->generateDisplayToken();
        session()->flash('one_time_display_token', $rawToken);

        ActivityLog::log(
            action: $isRegeneration ? 'display_token_regenerated' : 'display_token_generated',
            description: sprintf('Kredensial tautan layar antrean publik %s', $isRegeneration ? 'diperbarui (rotasi token)' : 'dibuat'),
            business: $business,
            user: $user
        );

        $message = $isRegeneration
            ? 'Tautan layar antrean baru berhasil dibuat. Tautan lama otomatis tidak dapat digunakan lagi.'
            : 'Tautan layar antrean publik berhasil dibuat.';

        return redirect()->route('merchant.display.index')->with('status', $message);
    }

    /**
     * Revoke public display access immediately.
     */
    public function revoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var Business|null $business */
        $business = $user->business;

        if (! $business) {
            return redirect()->route('merchant.onboarding');
        }

        $business->revokeDisplayAccess();

        ActivityLog::log(
            action: 'display_access_revoked',
            description: 'Tautan layar antrean publik dicabut dan akses dinonaktifkan',
            business: $business,
            user: $user
        );

        return redirect()->route('merchant.display.index')
            ->with('status', 'Akses layar antrean publik berhasil dicabut dan dinonaktifkan.');
    }
}


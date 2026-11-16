<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Services\DevDemoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DevDemoAccessController extends Controller
{
    public function __construct(
        protected DevDemoService $demoService
    ) {
        // Step 1: Memastikan environment local/testing
        abort_unless($this->demoService->isDevAllowed(), 404);
    }

    /**
     * Handle quick-login from login screen demo panel.
     */
    public function quickLogin(Request $request): RedirectResponse
    {
        // Step 2: Memvalidasi persona yang diizinkan (strict server-side allowlist)
        $validated = $request->validate([
            'persona' => ['required', 'string', Rule::in(['admin', 'owner', 'operator', 'customer'])],
        ]);

        $persona = $validated['persona'];

        // Step 7 & 8: Logout dari identitas sebelumnya & Invalidate session lama
        if (Auth::check()) {
            Auth::guard('web')->logout();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Step 3: Mencari / memastikan dedicated demo account
        try {
            $user = $this->demoService->resolveOrCreatePersonaUser($persona);
        } catch (\Throwable $e) {
            Log::error("Dev Demo {$persona} resolution failed: ".$e->getMessage());

            return redirect()->route('login')
                ->with('error', 'Gagal memproses akun demo '.ucfirst($persona).'. Silakan jalankan: php artisan db:seed');
        }

        if (! $user) {
            return redirect()->route('login')
                ->with('error', "Akun demo untuk persona '{$persona}' belum terkonfigurasi.");
        }

        // Step 4: Memastikan account aktif
        if (! $user->is_active) {
            return redirect()->route('login')
                ->with('error', "Akun demo '{$user->email}' dalam status non-aktif. Aktifkan akun terlebih dahulu.");
        }

        // Step 5: Memverifikasi role platform aktual
        if ($persona === 'admin' && ! $user->isAdmin()) {
            return redirect()->route('login')
                ->with('error', "Akun '{$user->email}' tidak memiliki hak akses platform admin.");
        }

        if ($persona === 'customer' && ! $user->isCustomer()) {
            return redirect()->route('login')
                ->with('error', "Akun '{$user->email}' tidak terdaftar sebagai pelanggan.");
        }

        if (in_array($persona, ['owner', 'operator'], true) && ! $user->isMerchant()) {
            return redirect()->route('login')
                ->with('error', "Akun '{$user->email}' tidak terdaftar sebagai merchant.");
        }

        // Step 6: Memverifikasi business membership bila diperlukan
        if ($persona === 'owner' && ! $user->isOwner()) {
            return redirect()->route('login')
                ->with('error', "Akun '{$user->email}' tidak terdaftar sebagai Owner pada unit usaha demo.");
        }

        if ($persona === 'operator' && ! $user->isOperator()) {
            return redirect()->route('login')
                ->with('error', "Akun '{$user->email}' tidak terdaftar sebagai Operator pada unit usaha demo.");
        }

        // Step 9: Regenerate session baru untuk keamanan
        $request->session()->regenerate();

        // Step 10: Login sebagai akun demo yang telah diverifikasi
        Auth::login($user);

        // Step 11: Redirect sesuai persona
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('success', 'Masuk sebagai '.$user->name.' (Admin AntreIn)');
        }

        if ($user->isCustomer()) {
            return redirect()->route('customer.dashboard')
                ->with('success', 'Masuk sebagai '.$user->name.' (Pelanggan AntreIn)');
        }

        return redirect()->route('merchant.dashboard')
            ->with('success', 'Masuk sebagai '.$user->name.' ('.ucfirst($user->businessRole() ?? 'Merchant').')');
    }

    /**
     * Handle floating role/persona switching.
     */
    public function switchPersona(Request $request): RedirectResponse
    {
        return $this->quickLogin($request);
    }
}

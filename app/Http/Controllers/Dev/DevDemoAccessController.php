<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Services\DevDemoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DevDemoAccessController extends Controller
{
    public function __construct(
        protected DevDemoService $demoService
    ) {
        abort_unless($this->demoService->isDevAllowed(), 404);
    }

    /**
     * Handle quick-login from login screen demo panel.
     */
    public function quickLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'persona' => ['required', 'string', Rule::in(['admin', 'owner', 'operator', 'customer'])],
        ]);

        $persona = $request->input('persona');

        if ($persona === 'customer') {
            if (Auth::check()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $this->demoService->ensureOwnerUser();

            return redirect()->route('customer.queue', ['slug' => DevDemoService::DEMO_SLUG])
                ->with('success', 'Masuk ke mode Tamu Pelanggan (Barbershop Garasi).');
        }

        $user = $this->demoService->resolveOrCreatePersonaUser($persona);

        if (! $user) {
            return redirect()->route('login')->with('error', 'Gagal memuat persona demo.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('success', 'Masuk sebagai ' . $user->name . ' (Admin AntreIn)');
        }

        return redirect()->route('merchant.dashboard')
            ->with('success', 'Masuk sebagai ' . $user->name . ' (' . ucfirst($user->businessRole() ?? 'Merchant') . ')');
    }

    /**
     * Handle floating role/persona switching.
     */
    public function switchPersona(Request $request): RedirectResponse
    {
        $request->validate([
            'persona' => ['required', 'string', Rule::in(['admin', 'owner', 'operator', 'customer'])],
        ]);

        $persona = $request->input('persona');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($persona === 'customer') {
            $this->demoService->ensureOwnerUser();

            return redirect()->route('customer.queue', ['slug' => DevDemoService::DEMO_SLUG])
                ->with('success', 'Beralih ke persona Pelanggan Tamu.');
        }

        $user = $this->demoService->resolveOrCreatePersonaUser($persona);

        if (! $user) {
            return redirect()->route('login')->with('error', 'Gagal memuat persona demo.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('success', 'Beralih ke persona ' . $user->name . ' (Admin AntreIn)');
        }

        return redirect()->route('merchant.dashboard')
            ->with('success', 'Beralih ke persona ' . $user->name . ' (' . ucfirst($user->businessRole() ?? 'Merchant') . ')');
    }
}


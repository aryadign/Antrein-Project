<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view for customers and UMKM owners.
     */
    public function create(Request $request): View
    {
        $defaultAccountType = $request->query('type') === 'customer' ? 'customer' : 'merchant';

        return view('auth.register', [
            'defaultAccountType' => $defaultAccountType,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * In accordance with AntreIn architecture:
     * - Public registration supports 'customer' (pelanggan) and 'merchant' (UMKM owner) only.
     * - Admin accounts can NEVER be registered through public endpoints.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'account_type' => ['nullable', 'string', 'in:customer,merchant'],
        ]);

        $accountType = $validated['account_type'] ?? 'merchant';
        $role = $accountType === 'customer' ? User::ROLE_CUSTOMER : User::ROLE_MERCHANT;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'is_active' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        ActivityLog::log(
            action: $role === User::ROLE_CUSTOMER ? 'register_customer' : 'register',
            description: $role === User::ROLE_CUSTOMER
                ? 'Pendaftaran akun pelanggan baru berhasil'
                : 'Pendaftaran akun UMKM baru berhasil',
            business: null,
            user: $user,
            properties: ['ip' => $request->ip(), 'role' => $role]
        );

        if ($user->isCustomer()) {
            return redirect()->intended(route('customer.dashboard'))
                ->with('status', 'Akun pelanggan berhasil dibuat! Silakan pilih UMKM untuk mengambil antrean.');
        }

        return redirect()->route('merchant.onboarding');
    }
}

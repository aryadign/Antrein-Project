<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BusinessMembership;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffInvitationController extends Controller
{
    /**
     * Show the staff invitation acceptance page.
     */
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $tokenHash = hash('sha256', $token);
        $invitation = StaffInvitation::with(['business', 'invitedBy'])
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $invitation) {
            return view('auth.staff-invitation', [
                'status' => 'not_found',
                'message' => 'Tautan undangan tidak valid atau tidak ditemukan.',
                'invitation' => null,
                'business' => null,
                'token' => $token,
                'userExists' => false,
            ]);
        }

        if ($invitation->isRevoked()) {
            return view('auth.staff-invitation', [
                'status' => 'revoked',
                'message' => 'Tautan undangan ini telah dicabut oleh pemilik usaha.',
                'invitation' => $invitation,
                'business' => $invitation->business,
                'token' => $token,
                'userExists' => false,
            ]);
        }

        if ($invitation->isAccepted()) {
            return view('auth.staff-invitation', [
                'status' => 'accepted',
                'message' => 'Tautan undangan ini sudah pernah digunakan dan diterima.',
                'invitation' => $invitation,
                'business' => $invitation->business,
                'token' => $token,
                'userExists' => false,
            ]);
        }

        if ($invitation->isExpired()) {
            return view('auth.staff-invitation', [
                'status' => 'expired',
                'message' => 'Tautan undangan ini telah kedaluwarsa. Silakan minta pemilik usaha untuk mengirimkan undangan baru.',
                'invitation' => $invitation,
                'business' => $invitation->business,
                'token' => $token,
                'userExists' => false,
            ]);
        }

        $currentUser = $request->user();
        $userExists = User::where('email', $invitation->email)->exists();

        // If currently authenticated
        if ($currentUser) {
            // Check if user is an admin
            if ($currentUser->isAdmin()) {
                return view('auth.staff-invitation', [
                    'status' => 'is_admin',
                    'message' => 'Akun Administrator tidak dapat bergabung sebagai staf operasional UMKM.',
                    'invitation' => $invitation,
                    'business' => $invitation->business,
                    'token' => $token,
                    'userExists' => $userExists,
                ]);
            }

            // Check email mismatch
            if (strtolower($currentUser->email) !== strtolower($invitation->email)) {
                return view('auth.staff-invitation', [
                    'status' => 'email_mismatch',
                    'message' => sprintf(
                        'Anda saat ini login sebagai %s, namun undangan ini ditujukan khusus untuk %s. Silakan logout terlebih dahulu untuk menerima undangan ini.',
                        $currentUser->email,
                        $invitation->email
                    ),
                    'invitation' => $invitation,
                    'business' => $invitation->business,
                    'token' => $token,
                    'userExists' => $userExists,
                ]);
            }

            // Check if already active member of THIS business
            $isMemberHere = BusinessMembership::where('user_id', $currentUser->id)
                ->where('business_id', $invitation->business_id)
                ->where('is_active', true)
                ->exists();

            if ($isMemberHere) {
                return redirect()->route('merchant.dashboard')
                    ->with('status', 'Anda sudah menjadi staf aktif di unit usaha ini.');
            }

            // Check if member of ANOTHER business
            $hasOtherBusiness = BusinessMembership::where('user_id', $currentUser->id)
                ->where('business_id', '!=', $invitation->business_id)
                ->exists();

            if ($hasOtherBusiness) {
                return view('auth.staff-invitation', [
                    'status' => 'already_member_other',
                    'message' => 'Akun Anda sudah terdaftar di unit usaha lain. Dalam rilis ini, satu akun pengguna hanya dapat terhubung ke satu UMKM.',
                    'invitation' => $invitation,
                    'business' => $invitation->business,
                    'token' => $token,
                    'userExists' => $userExists,
                ]);
            }
        }

        return view('auth.staff-invitation', [
            'status' => 'valid',
            'message' => null,
            'invitation' => $invitation,
            'business' => $invitation->business,
            'token' => $token,
            'userExists' => $userExists,
        ]);
    }

    /**
     * Accept a staff invitation and establish business membership.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $tokenHash = hash('sha256', $token);
        /** @var StaffInvitation|null $invitation */
        $invitation = StaffInvitation::with('business')
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $invitation || ! $invitation->isValid()) {
            return redirect()->route('invitations.show', ['token' => $token])
                ->withErrors(['general' => 'Tautan undangan tidak valid, telah dicabut, atau sudah kedaluwarsa.']);
        }

        $user = $request->user();

        // If not logged in, authenticate existing user or register new account
        if (! $user) {
            $existingUser = User::where('email', $invitation->email)->first();

            if ($existingUser) {
                $validated = $request->validate([
                    'password' => ['required', 'string'],
                ], [
                    'password.required' => 'Kata sandi wajib diisi untuk masuk.',
                ]);

                if (! Auth::attempt(['email' => $invitation->email, 'password' => $validated['password']])) {
                    return back()->withErrors(['password' => 'Kata sandi yang Anda masukkan salah.'])->withInput();
                }

                $request->session()->regenerate();
                $user = Auth::user();
            } else {
                $validated = $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'password' => ['required', 'string', 'confirmed', Password::defaults()],
                ], [
                    'name.required' => 'Nama lengkap wajib diisi.',
                    'password.required' => 'Kata sandi wajib diisi.',
                    'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
                ]);

                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $invitation->email,
                    'password' => Hash::make($validated['password']),
                    'role' => User::ROLE_MERCHANT,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);

                Auth::login($user);
                $request->session()->regenerate();
            }
        }

        // Validate user eligibility
        if ($user->isAdmin()) {
            abort(403, 'Akun Administrator tidak dapat bergabung sebagai staf operasional UMKM.');
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return back()->withErrors([
                'email' => sprintf('Akun yang sedang aktif (%s) tidak cocok dengan email tujuan undangan (%s).', $user->email, $invitation->email),
            ]);
        }

        // Single business per user check
        $otherBusinessMembership = BusinessMembership::where('user_id', $user->id)
            ->where('business_id', '!=', $invitation->business_id)
            ->first();

        if ($otherBusinessMembership) {
            return back()->withErrors([
                'general' => 'Akun Anda sudah terdaftar di unit usaha lain. Satu akun hanya dapat terhubung ke satu UMKM.',
            ]);
        }

        // Execute acceptance atomically
        DB::transaction(function () use ($invitation, $user) {
            BusinessMembership::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_id' => $invitation->business_id,
                    'role' => $invitation->role,
                    'is_active' => true,
                    'joined_at' => now(),
                ]
            );

            $invitation->update([
                'accepted_at' => now(),
            ]);

            ActivityLog::log(
                action: 'staff_invitation_accepted',
                description: sprintf('Staf operator %s (%s) menerima undangan dan resmi bergabung ke unit usaha', $user->name, $user->email),
                business: $invitation->business,
                user: $user
            );
        });

        return redirect()->route('merchant.dashboard')
            ->with('status', sprintf('Selamat bergabung di %s! Anda sekarang dapat mengoperasikan konsol antrean.', $invitation->business->name));
    }
}


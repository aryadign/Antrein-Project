<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffManagementController extends Controller
{
    /**
     * Display the staff and operator management dashboard for business owners (P1-01 & P1-02).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        $memberships = $business->memberships()
            ->with('user')
            ->orderByRaw("CASE WHEN role = 'owner' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'asc')
            ->get();

        $pendingInvitations = $business->pendingInvitations()
            ->with('invitedBy')
            ->latest()
            ->get();

        $activeStaffCount = $memberships->where('role', BusinessMembership::ROLE_OPERATOR)->where('is_active', true)->count();
        $ownerMembership = $memberships->firstWhere('role', BusinessMembership::ROLE_OWNER);

        return view('merchant.staff.index', [
            'business' => $business,
            'memberships' => $memberships,
            'pendingInvitations' => $pendingInvitations,
            'activeStaffCount' => $activeStaffCount,
            'ownerMembership' => $ownerMembership,
        ]);
    }

    /**
     * Issue a secure staff operator invitation for this business.
     */
    public function storeInvitation(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => 'Alamat email staf wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        $email = strtolower(trim($validated['email']));

        // 1. Prevent inviting the owner themselves
        if ($business->user && strtolower($business->user->email) === $email) {
            return back()->withErrors(['email' => 'Alamat email ini adalah akun Pemilik Usaha.'])->withInput();
        }

        // 2. Prevent inviting someone already in this business
        $isAlreadyMember = $business->memberships()
            ->whereHas('user', fn ($q) => $q->where('email', $email))
            ->exists();

        if ($isAlreadyMember) {
            return back()->withErrors(['email' => 'Pengguna dengan email ini sudah terdaftar sebagai anggota pada usaha Anda.'])->withInput();
        }

        // 3. Prevent inviting someone already registered in another business (Single business per user rule)
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if ($existingUser->isAdmin()) {
                return back()->withErrors(['email' => 'Akun Administrator tidak dapat diundang sebagai staf UMKM.'])->withInput();
            }

            if ($existingUser->businessMembership()->exists()) {
                return back()->withErrors(['email' => 'Pengguna ini sudah terdaftar pada UMKM lain. Satu akun hanya dapat terhubung ke satu UMKM.'])->withInput();
            }
        }

        // 4. Prevent duplicate pending invitations for same email
        $duplicatePending = $business->pendingInvitations()
            ->where('email', $email)
            ->exists();

        if ($duplicatePending) {
            return back()->withErrors(['email' => 'Undangan aktif untuk alamat email ini sudah ada dan belum kedaluwarsa.'])->withInput();
        }

        // 5. Generate secure invitation token
        [$plainToken, $tokenHash] = StaffInvitation::generateSecureToken();
        $expiryHours = (int) config('auth.staff_invitation_expiry_hours', 72);
        $expiresAt = now()->addHours($expiryHours);

        $invitation = StaffInvitation::create([
            'business_id' => $business->id,
            'invited_by_user_id' => $user->id,
            'email' => $email,
            'role' => StaffInvitation::ROLE_OPERATOR,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        // Record audit activity log (M9/M12)
        ActivityLog::log(
            action: 'staff_invitation_created',
            description: sprintf('Undangan staf operator dibuat untuk email %s (berlaku %d jam)', $email, $expiryHours),
            business: $business,
            user: $user
        );

        $invitationUrl = route('invitations.show', ['token' => $plainToken]);

        return redirect()->route('merchant.staff.index')
            ->with('status', sprintf('Undangan untuk %s berhasil dibuat! Silakan salin tautan undangan berikut.', $email))
            ->with('invitation_url', $invitationUrl)
            ->with('invited_email', $email);
    }

    /**
     * Revoke an active staff invitation before it is accepted.
     */
    public function revokeInvitation(Request $request, StaffInvitation $invitation): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($invitation->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak untuk mencabut undangan ini.');
        }

        if ($invitation->accepted_at !== null) {
            return back()->withErrors(['general' => 'Undangan telah diterima dan tidak dapat dicabut.']);
        }

        $invitation->update([
            'revoked_at' => now(),
        ]);

        ActivityLog::log(
            action: 'staff_invitation_revoked',
            description: sprintf('Undangan staf untuk email %s telah dicabut', $invitation->email),
            business: $business,
            user: $user
        );

        return redirect()->route('merchant.staff.index')
            ->with('status', sprintf('Undangan untuk %s berhasil dicabut.', $invitation->email));
    }

    /**
     * Toggle active/inactive status of an operator staff membership.
     */
    public function toggleStatus(Request $request, BusinessMembership $membership): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($membership->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola keanggotaan staf ini.');
        }

        // Primary Owner protection
        if ($membership->isOwner()) {
            abort(403, 'Akses akun Pemilik Utama tidak dapat dinonaktifkan.');
        }

        $newStatus = ! $membership->is_active;
        $membership->update([
            'is_active' => $newStatus,
        ]);

        $actionName = $newStatus ? 'staff_activated' : 'staff_deactivated';
        $statusLabel = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLog::log(
            action: $actionName,
            description: sprintf('Akses staf %s (%s) %s', $membership->user->name, $membership->user->email, $statusLabel),
            business: $business,
            user: $user
        );

        return redirect()->route('merchant.staff.index')
            ->with('status', sprintf('Akses staf %s berhasil %s.', $membership->user->name, $statusLabel));
    }

    /**
     * Remove an operator staff member from the business.
     */
    public function destroy(Request $request, BusinessMembership $membership): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;

        if ($membership->business_id !== $business->id) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus keanggotaan staf ini.');
        }

        // Primary Owner protection
        if ($membership->isOwner()) {
            abort(403, 'Akun Pemilik Utama tidak dapat dihapus dari keanggotaan usaha.');
        }

        $staffName = $membership->user->name;
        $staffEmail = $membership->user->email;

        $membership->delete();

        ActivityLog::log(
            action: 'staff_removed',
            description: sprintf('Staf operator %s (%s) dihapus dari unit usaha', $staffName, $staffEmail),
            business: $business,
            user: $user
        );

        return redirect()->route('merchant.staff.index')
            ->with('status', sprintf('Staf %s berhasil dihapus dari unit usaha Anda.', $staffName));
    }
}


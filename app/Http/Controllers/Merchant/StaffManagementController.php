<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCounter;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffManagementController extends Controller
{
    /**
     * Display the staff and operator management dashboard for business owners.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        /** @var Business $business */
        $business = $user->business;
        $organization = $user->primaryOrganization();

        $branches = ($user->canManageOrganization() && $organization)
            ? $organization->branches()
                ->with(['activeCounters' => fn ($q) => $q->orderBy('code')])
                ->orderBy('name')
                ->get()
            : collect([$business->load(['activeCounters' => fn ($q) => $q->orderBy('code')])]);

        $branchIds = $branches->pluck('id')->all();

        $memberships = BusinessMembership::query()
            ->whereIn('business_id', $branchIds)
            ->with(['user', 'business', 'assignedCounters'])
            ->orderByRaw("CASE WHEN role = 'owner' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'asc')
            ->get();

        $activeStaffCount = $memberships->where('role', BusinessMembership::ROLE_OPERATOR)->where('is_active', true)->count();
        $ownerMembership = $memberships->firstWhere('role', BusinessMembership::ROLE_OWNER);

        $countersByBranch = $branches->mapWithKeys(fn (Business $b): array => [
            (string) $b->id => $b->activeCounters->map(fn (BusinessCounter $c): array => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
            ])->values()->all(),
        ])->all();

        return view('merchant.staff.index', [
            'business' => $business,
            'branches' => $branches,
            'countersByBranch' => $countersByBranch,
            'memberships' => $memberships,
            'activeStaffCount' => $activeStaffCount,
            'ownerMembership' => $ownerMembership,
            'activeCounters' => $business->activeCounters()->orderBy('code')->get(),
        ]);
    }

    /**
     * Directly create a new operator staff account with email & password (no invitation links).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        /** @var Business $activeBusiness */
        $activeBusiness = $user->business;
        $organization = $user->managedOrganization();

        $allowedBranchIds = $organization
            ? $organization->branches()->pluck('id')->all()
            : [$activeBusiness->id];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'business_id' => ['nullable', 'integer', Rule::in($allowedBranchIds)],
            'business_counter_id' => ['nullable', 'integer'],
        ], [
            'name.required' => 'Nama lengkap staf wajib diisi.',
            'email.required' => 'Alamat email staf wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email ini sudah digunakan oleh akun lain.',
            'password.required' => 'Password awal staf wajib diisi.',
            'password.min' => 'Password staf minimal 8 karakter.',
            'business_id.in' => 'Cabang usaha yang dipilih tidak valid.',
        ]);

        $targetBusinessId = (int) ($validated['business_id'] ?? $activeBusiness->id);
        /** @var Business $targetBusiness */
        $targetBusiness = Business::findOrFail($targetBusinessId);

        $counter = null;
        if (! empty($validated['business_counter_id'])) {
            $counter = BusinessCounter::query()
                ->where('id', (int) $validated['business_counter_id'])
                ->where('business_id', $targetBusiness->id)
                ->where('is_active', true)
                ->first();

            if (! $counter) {
                return back()->withErrors([
                    'business_counter_id' => 'Loket yang dipilih tidak tersedia atau nonaktif di cabang ini.',
                ])->withInput();
            }
        } else {
            $counter = $targetBusiness->activeCounters()->orderBy('id')->first();
        }

        $email = strtolower(trim($validated['email']));
        $plainPassword = $validated['password'];

        DB::transaction(function () use ($user, $targetBusiness, $validated, $email, $plainPassword, $counter) {
            $staffUser = User::create([
                'name' => trim($validated['name']),
                'email' => $email,
                'password' => Hash::make($plainPassword),
                'role' => User::ROLE_MERCHANT,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $membership = BusinessMembership::create([
                'organization_id' => $targetBusiness->organization_id,
                'business_id' => $targetBusiness->id,
                'user_id' => $staffUser->id,
                'role' => BusinessMembership::ROLE_OPERATOR,
                'is_active' => true,
                'staff_password_encrypted' => Crypt::encryptString($plainPassword),
            ]);

            if ($counter) {
                $membership->assignedCounters()->sync([$counter->id]);
            }

            ActivityLog::log(
                action: 'staff_account_created',
                description: sprintf('Akun operator "%s" (%s) dibuat untuk cabang %s', $staffUser->name, $email, $targetBusiness->name),
                business: $targetBusiness,
                user: $user
            );
        });

        return redirect()->route('merchant.staff.index')
            ->with('status', sprintf('Akun staf %s (%s) berhasil dibuat dan siap digunakan login.', trim($validated['name']), $email));
    }

    /**
     * Update an operator staff member's login password and encrypted owner reference.
     */
    public function updatePassword(Request $request, BusinessMembership $membership): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeMembership($user, $membership);

        if ($membership->isOwner()) {
            abort(403, 'Password Pemilik Utama hanya dapat diubah melalui menu Pengaturan Akun.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
        ]);

        $plainPassword = $validated['password'];

        DB::transaction(function () use ($user, $membership, $plainPassword) {
            $membership->user->update([
                'password' => Hash::make($plainPassword),
            ]);

            $membership->update([
                'staff_password_encrypted' => Crypt::encryptString($plainPassword),
            ]);

            ActivityLog::log(
                action: 'staff_password_updated',
                description: sprintf('Password akun staf %s (%s) diperbarui oleh pemilik', $membership->user->name, $membership->user->email),
                business: $membership->business,
                user: $user
            );
        });

        return redirect()->route('merchant.staff.index')
            ->with('status', sprintf('Password login untuk staf %s berhasil diperbarui.', $membership->user->name));
    }

    /**
     * Toggle active/inactive status of an operator staff membership.
     */
    public function toggleStatus(Request $request, BusinessMembership $membership): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeMembership($user, $membership);

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
            business: $membership->business,
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
        $this->authorizeMembership($user, $membership);

        // Primary Owner protection
        if ($membership->isOwner()) {
            abort(403, 'Akun Pemilik Utama tidak dapat dihapus dari keanggotaan usaha.');
        }

        $staffName = $membership->user->name;
        $staffEmail = $membership->user->email;
        $targetBusiness = $membership->business;

        $membership->delete();

        ActivityLog::log(
            action: 'staff_removed',
            description: sprintf('Staf operator %s (%s) dihapus dari unit usaha', $staffName, $staffEmail),
            business: $targetBusiness,
            user: $user
        );

        return redirect()->route('merchant.staff.index')
            ->with('status', sprintf('Staf %s berhasil dihapus dari unit usaha Anda.', $staffName));
    }

    /**
     * Authorize owner/manager access to a staff membership within the active business or organization.
     */
    protected function authorizeMembership(User $user, BusinessMembership $membership): void
    {
        $activeBusiness = $user->business;
        $organization = $user->managedOrganization();

        $sameActiveBusiness = $activeBusiness && $membership->business_id === $activeBusiness->id;
        $sameOrganization = $organization && $membership->business && $membership->business->organization_id === $organization->id;

        if (! $sameActiveBusiness && ! $sameOrganization) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola keanggotaan staf ini.');
        }
    }
}

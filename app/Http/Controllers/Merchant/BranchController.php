<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Support\UniqueBranchSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BranchController extends Controller
{
    /**
     * Display the organization's branches that the current user may access.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $organization = $user->primaryOrganization();

        $query = $user->canManageOrganization() && $organization
            ? $organization->branches()->getQuery()
            : $user->accessibleBusinessesQuery();

        if ($organization) {
            $query->where('organization_id', $organization->id);
        }

        $branches = $query
            ->with(['category'])
            ->withCount(['services', 'memberships', 'counters'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $allBranches = $organization
            ? $organization->branches()->getQuery()
            : $user->accessibleBusinessesQuery();

        return view('merchant.branches.index', [
            'organization' => $organization,
            'branches' => $branches,
            'totalBranches' => (clone $allBranches)->count(),
            'activeBranches' => (clone $allBranches)->where('is_active', true)->count(),
            'totalServices' => (clone $allBranches)->withCount('services')->get()->sum('services_count'),
            'totalCounters' => (clone $allBranches)->withCount('counters')->get()->sum('counters_count'),
            'canManage' => $user->canManageOrganization(),
            'activeBranchId' => $user->business?->id,
        ]);
    }

    /**
     * Switch the merchant's active branch for the current session.
     */
    public function switch(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
        ]);

        $user = $request->user();
        $business = Business::findOrFail($validated['business_id']);

        if (! $user->canAccessBusiness($business)) {
            abort(403, 'Anda tidak memiliki hak akses ke cabang ini.');
        }

        $request->session()->put('active_business_id', $business->id);
        $user->forgetActiveBusiness();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'business_id' => $business->id,
                    'business_name' => $business->name,
                    'business_slug' => $business->slug,
                ],
            ]);
        }

        return redirect()->route('merchant.dashboard')
            ->with('status', sprintf('Cabang aktif diubah ke %s.', $business->name));
    }

    /**
     * Show the branch creation form.
     */
    public function create(Request $request): View
    {
        $organization = $request->user()->managedOrganization();

        return view('merchant.branches.form', [
            'organization' => $organization,
            'branch' => new Business([
                'timezone' => $organization?->timezone ?: 'Asia/Makassar',
                'status' => Business::STATUS_TUTUP,
                'is_active' => true,
            ]),
            'categories' => BusinessCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'sourceBranch' => $request->user()->business,
            'isEdit' => false,
        ]);
    }

    /**
     * Create a new branch under the current organization.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $organization = $user->managedOrganization();

        if (! $organization) {
            abort(403, 'Anda tidak memiliki hak untuk membuat cabang.');
        }

        $validated = $this->validateBranch($request);

        $branch = DB::transaction(function () use ($user, $organization, $validated) {
            $slug = UniqueBranchSlug::generate(
                $validated['slug'] ?? null,
                $validated['name']
            );

            /** @var Business $branch */
            $branch = Business::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'category_id' => $validated['category_id'],
                'name' => trim($validated['name']),
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'address' => $validated['address'],
                'phone' => $validated['phone'],
                'timezone' => $validated['timezone'],
                'status' => $validated['status'] ?? Business::STATUS_TUTUP,
                'is_active' => true,
            ]);

            $sourceBranch = $user->business;

            if ($sourceBranch && $sourceBranch->id !== $branch->id) {
                $this->copyOperationalDefaults($sourceBranch, $branch);
            }

            ActivityLog::log(
                action: 'branch_created',
                description: sprintf('Cabang baru "%s" dibuat pada organisasi %s', $branch->name, $organization->name),
                business: $branch,
                user: $user,
                properties: ['organization_id' => $organization->id]
            );

            return $branch;
        });

        return redirect()->route('merchant.branches.index')
            ->with('status', sprintf('Cabang %s berhasil dibuat. Gunakan "Pilih Cabang" untuk mulai mengoperasikannya.', $branch->name))
            ->with('created_branch_id', $branch->id);
    }

    /**
     * Show the branch edit form.
     */
    public function edit(Request $request, Business $business): View
    {
        $organization = $this->authorizeBranch($request, $business);

        return view('merchant.branches.form', [
            'organization' => $organization,
            'branch' => $business,
            'categories' => BusinessCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'sourceBranch' => null,
            'isEdit' => true,
        ]);
    }

    /**
     * Update branch identity and location details.
     */
    public function update(Request $request, Business $business): RedirectResponse
    {
        $organization = $this->authorizeBranch($request, $business);

        $validated = $this->validateBranch($request, $business);

        $business->update([
            'category_id' => $validated['category_id'],
            'name' => trim($validated['name']),
            'slug' => UniqueBranchSlug::generate($validated['slug'] ?? null, $validated['name'], $business->id),
            'description' => $validated['description'] ?? null,
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'timezone' => $validated['timezone'],
        ]);

        ActivityLog::log(
            action: 'branch_updated',
            description: sprintf('Data cabang "%s" diperbarui', $business->name),
            business: $business,
            user: $request->user(),
            properties: ['organization_id' => $organization->id]
        );

        return redirect()->route('merchant.branches.index')
            ->with('status', sprintf('Data cabang %s berhasil diperbarui.', $business->name));
    }

    /**
     * Activate or deactivate a branch inside the current organization.
     */
    public function toggleActive(Request $request, Business $business): RedirectResponse
    {
        $organization = $this->authorizeBranch($request, $business);

        $willDeactivate = $business->is_active;

        if ($willDeactivate && $organization->branches()->where('is_active', true)->count() <= 1) {
            return back()->withErrors([
                'general' => 'Minimal satu cabang harus tetap aktif di dalam organisasi.',
            ]);
        }

        $business->update([
            'is_active' => ! $business->is_active,
            // Deactivated branches must not accept or display new queues.
            'status' => $willDeactivate ? Business::STATUS_TUTUP : $business->status,
        ]);

        ActivityLog::log(
            action: $willDeactivate ? 'branch_deactivated' : 'branch_activated',
            description: sprintf(
                'Cabang "%s" %s oleh pengelola organisasi',
                $business->name,
                $willDeactivate ? 'dinonaktifkan' : 'diaktifkan'
            ),
            business: $business,
            user: $request->user(),
            properties: ['organization_id' => $organization->id]
        );

        return redirect()->route('merchant.branches.index')
            ->with('status', sprintf(
                'Cabang %s berhasil %s.',
                $business->name,
                $willDeactivate ? 'dinonaktifkan' : 'diaktifkan'
            ));
    }

    /**
     * Ensure the target branch belongs to the organization managed by the user.
     */
    protected function authorizeBranch(Request $request, Business $business): \App\Models\BusinessOrganization
    {
        $organization = $request->user()->managedOrganization();

        if (! $organization || $business->organization_id !== $organization->id) {
            abort(403, 'Anda tidak memiliki hak untuk mengelola cabang ini.');
        }

        return $organization;
    }

    /**
     * Validate branch management input.
     *
     * @return array<string, mixed>
     */
    protected function validateBranch(Request $request, ?Business $business = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', 'unique:businesses,slug'.($business ? ','.$business->id : '')],
            'category_id' => ['required', 'integer', 'exists:business_categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:20'],
            'timezone' => ['required', 'string', 'in:Asia/Makassar,Asia/Jakarta,Asia/Jayapura'],
            'status' => ['nullable', 'string', 'in:buka,tutup,jeda'],
        ], [
            'name.required' => 'Nama cabang wajib diisi.',
            'slug.unique' => 'Slug cabang sudah digunakan oleh cabang lain.',
            'address.required' => 'Alamat cabang wajib diisi.',
            'phone.required' => 'Nomor telepon cabang wajib diisi.',
        ]);
    }

    /**
     * Copy hours and services from the reference branch so the new branch is immediately operable.
     */
    protected function copyOperationalDefaults(Business $source, Business $target): void
    {
        $hours = BusinessHour::query()->where('business_id', $source->id)->get();

        if ($hours->isEmpty()) {
            foreach (BusinessHour::DAY_NAMES as $dayOfWeek => $dayName) {
                BusinessHour::create([
                    'business_id' => $target->id,
                    'day_of_week' => $dayOfWeek,
                    'open_time' => '09:00',
                    'close_time' => '21:00',
                    'is_closed' => $dayOfWeek === 0,
                ]);
            }
        } else {
            foreach ($hours as $hour) {
                BusinessHour::create([
                    'business_id' => $target->id,
                    'day_of_week' => $hour->day_of_week,
                    'open_time' => $hour->open_time,
                    'close_time' => $hour->close_time,
                    'is_closed' => $hour->is_closed,
                ]);
            }
        }

        $services = Service::query()->where('business_id', $source->id)->get();

        foreach ($services as $service) {
            Service::create([
                'business_id' => $target->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'daily_quota' => $service->daily_quota,
                'is_active' => $service->is_active,
            ]);
        }
    }
}

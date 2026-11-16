<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BusinessMembership;
use App\Models\User;
use App\Services\UserDeactivationService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function __construct(
        protected UserDeactivationService $deactivationService
    ) {}

    /**
     * Display a listing of all platform users with role and status filtering.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = $request->query('role');
        $status = $request->query('status'); // 'active', 'inactive'

        $query = User::query()->with([
            'business',
            'businessMemberships.business',
            'businessMemberships.assignedCounters',
            'ownedBusinesses',
        ]);

        if ($search !== '') {
            $normalized = Str::lower($search);

            $query->where(function ($q) use ($search, $normalized) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('ownedBusinesses', function ($bq) use ($search) {
                        $bq->where('name', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
                    })
                    ->orWhereHas('businessMemberships.business', function ($bq) use ($search) {
                        $bq->where('name', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%");
                    });

                if (Str::contains($normalized, 'operator')) {
                    $q->orWhere(function ($oq) {
                        $oq->where('role', User::ROLE_MERCHANT)
                            ->whereHas('businessMemberships', fn ($mq) => $mq->where('role', BusinessMembership::ROLE_OPERATOR));
                    });
                }

                if (Str::contains($normalized, ['owner', 'pemilik'])) {
                    $q->orWhere(function ($owq) {
                        $owq->where('role', User::ROLE_MERCHANT)
                            ->where(function ($sub) {
                                $sub->whereHas('ownedBusinesses')
                                    ->orWhereHas('businessMemberships', fn ($mq) => $mq->where('role', BusinessMembership::ROLE_OWNER));
                            });
                    });
                }

                if (Str::contains($normalized, ['pelanggan', 'customer'])) {
                    $q->orWhere('role', User::ROLE_CUSTOMER);
                }

                if (Str::contains($normalized, 'admin')) {
                    $q->orWhere('role', User::ROLE_ADMIN);
                }
            });
        }

        if ($role === User::ROLE_ADMIN) {
            $query->where('role', User::ROLE_ADMIN);
        } elseif ($role === User::ROLE_CUSTOMER) {
            $query->where('role', User::ROLE_CUSTOMER);
        } elseif ($role === User::ROLE_MERCHANT) {
            $query->where('role', User::ROLE_MERCHANT);
        } elseif ($role === BusinessMembership::ROLE_OPERATOR) {
            $query->where('role', User::ROLE_MERCHANT)
                ->whereHas('businessMemberships', fn ($mq) => $mq->where('role', BusinessMembership::ROLE_OPERATOR));
        } elseif ($role === BusinessMembership::ROLE_OWNER) {
            $query->where('role', User::ROLE_MERCHANT)
                ->where(function ($sub) {
                    $sub->whereHas('ownedBusinesses')
                        ->orWhereHas('businessMemberships', fn ($mq) => $mq->where('role', BusinessMembership::ROLE_OWNER));
                });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->latest('id')->paginate(10)->withQueryString();

        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', User::ROLE_ADMIN)->count(),
            'merchants' => User::where('role', User::ROLE_MERCHANT)->count(),
            'operators' => User::where('role', User::ROLE_MERCHANT)
                ->whereHas('businessMemberships', fn ($mq) => $mq->where('role', BusinessMembership::ROLE_OPERATOR))
                ->count(),
            'customers' => User::where('role', User::ROLE_CUSTOMER)->count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return view('admin.users.index', [
            'users' => $users,
            'stats' => $stats,
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    /**
     * Display the details of a specific user.
     */
    public function show(User $user): View
    {
        $user->load([
            'business.category',
            'businessMemberships.business.category',
            'businessMemberships.assignedCounters',
            'ownedBusinesses.category',
        ]);

        $userLogs = ActivityLog::where('user_id', $user->id)
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.users.show', [
            'user' => $user,
            'userLogs' => $userLogs,
        ]);
    }

    /**
     * Deactivate a user account with interlock and last active admin protection.
     */
    public function deactivate(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'emergency_force' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->deactivationService->deactivate(
                targetUser: $user,
                performedBy: $request->user(),
                emergencyForce: ! empty($validated['emergency_force']),
                reason: $validated['reason'] ?? null
            );

            if ($request->wantsJson()) {
                return response()->json($result);
            }

            return back()->with('status', $result['message']);
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withErrors(['deactivation' => $e->getMessage()])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Activate a previously deactivated user account.
     */
    public function activate(Request $request, User $user): RedirectResponse|JsonResponse
    {
        try {
            $result = $this->deactivationService->activate(
                targetUser: $user,
                performedBy: $request->user()
            );

            if ($request->wantsJson()) {
                return response()->json($result);
            }

            return back()->with('status', $result['message']);
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withErrors(['activation' => $e->getMessage()])
                ->with('error', $e->getMessage());
        }
    }
}

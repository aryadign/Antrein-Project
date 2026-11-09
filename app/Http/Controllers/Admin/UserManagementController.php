<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\UserDeactivationService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $query = User::query()->with('business');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role && in_array($role, [User::ROLE_ADMIN, User::ROLE_MERCHANT])) {
            $query->where('role', $role);
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
        $user->load('business.category');

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

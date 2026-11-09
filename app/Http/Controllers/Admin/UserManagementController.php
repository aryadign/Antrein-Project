<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserDeactivationService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        protected UserDeactivationService $deactivationService
    ) {}

    /**
     * Deactivate a user account with interlock enforcement.
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

            return back()->withErrors(['deactivation' => $e->getMessage()]);
        }
    }
}


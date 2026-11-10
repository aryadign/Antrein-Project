<?php

namespace App\Http\Middleware;

use App\Models\BusinessMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessRole
{
    /**
     * Handle an incoming request to verify that the user has the required business membership role.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles Allowed business roles: 'owner', 'operator'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        // Platform administrator has platform roles, not business-level roles
        if ($user->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Akses terbatas untuk akun Merchant.');
        }

        // Resolve or ensure membership
        $membership = $user->businessMembership;

        if (! $membership) {
            $business = $user->business;
            if ($business && $business->user_id === $user->id) {
                $membership = BusinessMembership::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'business_id' => $business->id,
                        'role' => BusinessMembership::ROLE_OWNER,
                        'is_active' => true,
                        'joined_at' => $business->created_at ?? now(),
                    ]
                );
            }
        }

        if (! $membership || ! $membership->is_active) {
            abort(Response::HTTP_FORBIDDEN, 'Akses keanggotaan staf UMKM Anda telah dinonaktifkan atau tidak ditemukan.');
        }

        if (! in_array($membership->role, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Akses tidak diizinkan. Tindakan ini memerlukan hak akses Pemilik (Owner).');
        }

        return $next($request);
    }
}


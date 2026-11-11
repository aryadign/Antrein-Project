<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Models\BusinessMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessRole
{
    /**
     * Handle an incoming request to verify that the user has the required role
     * in the currently active branch.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles Allowed effective branch roles: 'owner', 'operator'
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

        /** @var Business|null $business */
        $business = $user->business;

        if (! $business) {
            abort(Response::HTTP_FORBIDDEN, 'Akun Anda belum terhubung ke unit usaha mana pun.');
        }

        // Ensure legacy direct owners always have a branch membership record.
        if ($business->user_id === $user->id) {
            BusinessMembership::firstOrCreate(
                ['business_id' => $business->id, 'user_id' => $user->id],
                [
                    'organization_id' => $business->organization_id,
                    'role' => BusinessMembership::ROLE_OWNER,
                    'is_active' => true,
                    'joined_at' => $business->created_at ?? now(),
                ]
            );
        }

        $role = $user->roleInBusiness($business);

        if (! $role) {
            abort(Response::HTTP_FORBIDDEN, 'Akses keanggotaan staf UMKM Anda telah dinonaktifkan atau tidak ditemukan.');
        }

        if (! in_array($role, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Akses tidak diizinkan. Tindakan ini memerlukan hak akses Pemilik (Owner).');
        }

        return $next($request);
    }
}

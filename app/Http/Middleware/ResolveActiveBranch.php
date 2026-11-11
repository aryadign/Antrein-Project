<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveBranch
{
    /**
     * Validate and normalize the merchant's active branch stored in the session.
     *
     * The session value is never trusted on its own: it must resolve to a branch
     * the authenticated user is actually authorized to access.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin() || ! $user->isMerchant() || ! $request->hasSession()) {
            return $next($request);
        }

        $sessionBusinessId = $request->session()->get('active_business_id');

        if ($sessionBusinessId) {
            $isAuthorized = $user->accessibleBusinessesQuery()
                ->whereKey((int) $sessionBusinessId)
                ->exists();

            if (! $isAuthorized) {
                $request->session()->forget('active_business_id');
                $user->forgetActiveBusiness();
            }
        }

        $business = $user->activeBusiness();

        if ($business) {
            $request->session()->put('active_business_id', $business->id);
        }

        return $next($request);
    }
}

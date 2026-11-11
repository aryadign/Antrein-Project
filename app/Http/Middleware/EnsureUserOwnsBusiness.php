<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserOwnsBusiness
{
    /**
     * Handle an incoming request and verify branch access for the bound business.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        // Platform administrators have cross-tenant read privileges where allowed
        if ($user->isAdmin()) {
            return $next($request);
        }

        $businessParam = $request->route('business');

        if ($businessParam) {
            $business = $businessParam instanceof Business
                ? $businessParam
                : Business::find((int) $businessParam);

            if (! $business || ! $user->canAccessBusiness($business)) {
                abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses terhadap data usaha ini.');
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessApproved
{
    /**
     * Redirect merchant accounts whose active business is pending or rejected
     * to the waiting / approval status page.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isMerchant()) {
            $business = $user->business;

            if ($business && ! $business->isApproved()) {
                return redirect()->route('merchant.approval.status');
            }
        }

        return $next($request);
    }
}

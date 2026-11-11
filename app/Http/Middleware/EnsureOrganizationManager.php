<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationManager
{
    /**
     * Restrict organization-level actions to active organization owners/administrators.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Akses terbatas untuk pengelola organisasi UMKM.');
        }

        if (! $user->canManageOrganization()) {
            abort(Response::HTTP_FORBIDDEN, 'Tindakan ini memerlukan hak akses Pemilik atau Administrator Organisasi.');
        }

        return $next($request);
    }
}

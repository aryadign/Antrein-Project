<?php

use App\Http\Middleware\EnsureBusinessApproved;
use App\Http\Middleware\EnsureBusinessRole;
use App\Http\Middleware\EnsureMerchantOnboarded;
use App\Http\Middleware\EnsureOrganizationManager;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserOwnsBusiness;
use App\Http\Middleware\ResolveActiveBranch;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hosting platforms terminate HTTPS at a proxy; trust its forwarded headers so URLs,
        // secure cookies and the camera (HTTPS-only) work. Override with TRUSTED_PROXIES if needed.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));

        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            if ($user?->isAdmin()) {
                return route('admin.dashboard');
            }

            if ($user?->isCustomer()) {
                return route('customer.dashboard');
            }

            if ($user?->isMerchant()) {
                if (! $user->hasCompletedOnboarding()) {
                    return route('merchant.onboarding');
                }

                if ($user->business && ! $user->business->isApproved()) {
                    return route('merchant.approval.status');
                }

                return route('merchant.dashboard');
            }

            return route('login');
        });

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
            'onboarded' => EnsureMerchantOnboarded::class,
            'approved.business' => EnsureBusinessApproved::class,
            'owns.business' => EnsureUserOwnsBusiness::class,
            'business.role' => EnsureBusinessRole::class,
            'active.branch' => ResolveActiveBranch::class,
            'organization.manager' => EnsureOrganizationManager::class,
        ]);

        $middleware->appendToGroup('web', EnsureUserIsActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

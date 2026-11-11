<?php

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
        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            if ($user?->isAdmin()) {
                return route('admin.dashboard');
            }

            if ($user?->hasCompletedOnboarding()) {
                return route('merchant.dashboard');
            }

            if ($user) {
                return route('merchant.onboarding');
            }

            return route('login');
        });

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'onboarded' => \App\Http\Middleware\EnsureMerchantOnboarded::class,
            'owns.business' => \App\Http\Middleware\EnsureUserOwnsBusiness::class,
            'business.role' => \App\Http\Middleware\EnsureBusinessRole::class,
            'active.branch' => \App\Http\Middleware\ResolveActiveBranch::class,
            'organization.manager' => \App\Http\Middleware\EnsureOrganizationManager::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureUserIsActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

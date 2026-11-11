<?php

use App\Http\Controllers\Admin\BusinessCategoryController;
use App\Http\Controllers\Admin\BusinessManagementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PlatformSettingController;
use App\Http\Controllers\Admin\QueueMonitoringController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\StaffInvitationController;
use App\Http\Controllers\Broadcasting\TicketBroadcastAuthController;
use App\Http\Controllers\Customer\CustomerPushSubscriptionController;
use App\Http\Controllers\Customer\QueueTicketController;
use App\Http\Controllers\Display\PublicQueueDisplayController;
use App\Http\Controllers\Merchant\CounterManagementController;
use App\Http\Controllers\Merchant\BranchController;
use App\Http\Controllers\Merchant\OrganizationController;
use App\Http\Controllers\Merchant\OrganizationReportController;
use App\Http\Controllers\Merchant\DashboardController;
use App\Http\Controllers\Merchant\DisplayManagementController;
use App\Http\Controllers\Merchant\NotificationPreferenceController;
use App\Http\Controllers\Merchant\OnboardingController;
use App\Http\Controllers\Merchant\QueueController;
use App\Http\Controllers\Merchant\QueueOperationController;
use App\Http\Controllers\Merchant\ReportController;
use App\Http\Controllers\Merchant\ReportExportController;
use App\Http\Controllers\Merchant\ServiceController;
use App\Http\Controllers\Merchant\SettingsController;
use App\Http\Controllers\Merchant\StaffManagementController;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — AntreIn Platform
|--------------------------------------------------------------------------
*/

// Health Check Endpoint (M2-09)
Route::get('/health', function () {
    $dbOk = false;
    $dbError = null;

    try {
        DB::connection()->getPdo();
        $dbOk = true;
    } catch (\Throwable $e) {
        $dbError = $e->getMessage();
    }

    $status = $dbOk ? 200 : 503;

    return response()->json([
        'status' => $dbOk ? 'healthy' : 'unhealthy',
        'application' => config('app.name'),
        'environment' => config('app.env'),
        'timezone' => config('app.timezone'),
        'database' => [
            'connected' => $dbOk,
            'driver' => config('database.default'),
            'error' => $dbError,
        ],
        'timestamp' => now()->toIso8601String(),
    ], $status);
});

// Root Route: AntreIn Public Homepage
Route::get('/', function () {
    return view('home');
})->name('home');

// Customer Public Queue Routes (Milestone 6)
Route::get('/q/{slug}', [QueueTicketController::class, 'show'])->name('customer.queue');
Route::get('/q/{slug}/take-queue', [QueueTicketController::class, 'create'])->name('customer.queue.create');
Route::post('/q/{slug}/take-queue', [QueueTicketController::class, 'store'])->name('customer.queue.take');
Route::get('/q/track/{token}', [QueueTicketController::class, 'track'])->name('customer.queue.track');
Route::get('/ticket/{token}', [QueueTicketController::class, 'track'])->name('customer.ticket.show');
Route::post('/q/track/{token}/review', [\App\Http\Controllers\Customer\CustomerReviewController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('customer.review.store');
Route::post('/push-subscriptions/customer', [CustomerPushSubscriptionController::class, 'store'])->name('customer.push.subscribe');
Route::delete('/push-subscriptions/customer', [CustomerPushSubscriptionController::class, 'destroy'])->name('customer.push.unsubscribe');
Route::post('/broadcasting/auth/ticket', [TicketBroadcastAuthController::class, 'authenticate'])->name('broadcasting.auth.ticket');

// Public Queue TV/Monitor Display Routes (Milestone 16 - P1-08)
Route::get('/display/{token}', [PublicQueueDisplayController::class, 'show'])->name('display.show');
Route::get('/display/{token}/data', [PublicQueueDisplayController::class, 'data'])->name('display.data');

// Design System & Layout Preview Testbed (Internal / Development Only)
Route::prefix('preview')->name('preview.')->group(function () {
    Route::get('/design-system', function () {
        abort_if(app()->isProduction(), 404);
        return view('preview.design-system');
    })->name('design-system');

    Route::get('/admin', function () {
        abort_if(app()->isProduction(), 404);
        return view('preview.admin');
    })->name('admin');

    Route::get('/merchant', function () {
        abort_if(app()->isProduction(), 404);
        return view('preview.merchant');
    })->name('merchant');

    Route::get('/customer', function () {
        abort_if(app()->isProduction(), 404);
        return view('preview.customer');
    })->name('customer');

    Route::get('/auth', function () {
        abort_if(app()->isProduction(), 404);
        return view('preview.auth');
    })->name('auth');
});

Route::get('/design-system', function () {
    abort_if(app()->isProduction(), 404);
    return view('preview.design-system');
})->name('design-system');

/*
|--------------------------------------------------------------------------
| Developer Demo Access & Role Switching (Local & Testing Environments Only)
|--------------------------------------------------------------------------
*/
if (app()->environment(['local', 'testing'])) {
    Route::prefix('dev')->name('dev.')->group(function () {
        Route::post('/quick-login', [\App\Http\Controllers\Dev\DevDemoAccessController::class, 'quickLogin'])->name('quick-login');
        Route::post('/switch-persona', [\App\Http\Controllers\Dev\DevDemoAccessController::class, 'switchPersona'])->name('switch-persona');
    });
}

/*
|--------------------------------------------------------------------------
| Staff Operator Invitation Routes (P1-01 & P1-02)
|--------------------------------------------------------------------------
*/
Route::get('/invite/{token}', [StaffInvitationController::class, 'show'])->name('invitations.show');
Route::post('/invite/{token}/accept', [StaffInvitationController::class, 'accept'])
    ->middleware('throttle:10,1')
    ->name('invitations.accept');

/*
|--------------------------------------------------------------------------
| Authentication Routes (Guest Only)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated Shared Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (role:admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // Platform Dashboard (M8-01 to M8-07)
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Business Management (M8-08 to M8-14)
        Route::get('/businesses', [BusinessManagementController::class, 'index'])->name('businesses.index');
        Route::get('/umkm', [BusinessManagementController::class, 'index'])->name('umkm'); // Alias for nav
        Route::get('/businesses/{business}', [BusinessManagementController::class, 'show'])->name('businesses.show');
        Route::patch('/businesses/{business}/toggle-suspension', [BusinessManagementController::class, 'toggleSuspension'])->name('businesses.toggle-suspension');

        // User Management (M8-15 to M8-19)
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/activate', [UserManagementController::class, 'activate'])->name('users.activate');

        // Queue Monitoring (M8-20 to M8-26, read-only)
        Route::get('/queues', [QueueMonitoringController::class, 'index'])->name('queues.index');
        Route::get('/queues/{ticket}', [QueueMonitoringController::class, 'show'])->name('queues.show');

        // Business Categories Management (M4 / M8 integration)
        Route::get('/categories', [BusinessCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [BusinessCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [BusinessCategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/toggle-status', [BusinessCategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
        Route::delete('/categories/{category}', [BusinessCategoryController::class, 'destroy'])->name('categories.destroy');

        // Platform Settings (M8-27 to M8-29)
        Route::get('/settings', [PlatformSettingController::class, 'show'])->name('settings');
        Route::put('/settings', [PlatformSettingController::class, 'update'])->name('settings.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Merchant Routes (role:merchant)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:merchant', 'active.branch'])->prefix('umkm')->name('merchant.')->group(function () {
        // Onboarding flow (Accessible before completing onboarding)
        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
        Route::post('/onboarding/step-1', [OnboardingController::class, 'storeStep1'])->name('onboarding.step1');
        Route::post('/onboarding/step-2', [OnboardingController::class, 'storeStep2'])->name('onboarding.step2');
        Route::post('/onboarding/step-3', [OnboardingController::class, 'storeStep3'])->name('onboarding.step3');
        Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
        Route::get('/onboarding/qr-download', [OnboardingController::class, 'downloadQr'])->name('onboarding.download-qr');

        // Operational merchant routes (Restricted by 'onboarded' middleware)
        Route::middleware('onboarded')->group(function () {
            // Shared Operational Routes (Accessible to both Owner and Operator)
            Route::middleware('business.role:owner,operator')->group(function () {
                Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

                // Queue Management & Views (Milestone 7)
                Route::get('/queues', [QueueController::class, 'index'])->name('queues');
                Route::get('/queues/qr', [QueueController::class, 'qr'])->name('queues.qr');
                Route::get('/queues/{ticket}', [QueueController::class, 'show'])->name('queues.show');

                // Queue Operations (Milestone 5 & Milestone 12 & Milestone 13)
                Route::post('/queues/select-counter', [QueueOperationController::class, 'selectCounter'])->name('queues.select-counter');
                Route::post('/queues/walk-in', [QueueOperationController::class, 'walkIn'])->name('queues.walk-in');
                Route::post('/queues/call-next', [QueueOperationController::class, 'callNext'])->name('queues.call-next');
                Route::post('/queues/{ticket}/recall', [QueueOperationController::class, 'recall'])->name('queues.recall');
                Route::post('/queues/{ticket}/start-service', [QueueOperationController::class, 'startService'])->name('queues.start-service');
                Route::post('/queues/{ticket}/complete', [QueueOperationController::class, 'complete'])->name('queues.complete');
                Route::post('/queues/{ticket}/skip', [QueueOperationController::class, 'skip'])->name('queues.skip');
                Route::get('/queues/status', [QueueOperationController::class, 'status'])->name('queues.status');

                // Notification Preferences & Web Push Subscriptions (Milestone 15 - P1-05)
                Route::put('/settings/notifications', [NotificationPreferenceController::class, 'update'])->name('settings.notifications.update');
                Route::post('/settings/push-subscription', [NotificationPreferenceController::class, 'storeSubscription'])->name('settings.push.subscribe');
                Route::delete('/settings/push-subscription', [NotificationPreferenceController::class, 'destroySubscription'])->name('settings.push.unsubscribe');

                // Customer Reviews & Feedback Monitoring (Milestone 17 - P1-09)
                Route::get('/reviews', [\App\Http\Controllers\Merchant\CustomerReviewController::class, 'index'])->name('reviews.index');

                // Multi-Branch switching & branch overview (Milestone 19 - P2-01)
                Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
                Route::post('/branches/switch', [BranchController::class, 'switch'])->name('branches.switch');
            });

            // Organization-level management (Milestone 19 - P2-01)
            Route::middleware('organization.manager')->group(function () {
                Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
                Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
                Route::get('/branches/{business}/edit', [BranchController::class, 'edit'])->name('branches.edit');
                Route::put('/branches/{business}', [BranchController::class, 'update'])->name('branches.update');
                Route::patch('/branches/{business}/toggle-active', [BranchController::class, 'toggleActive'])->name('branches.toggle-active');

                Route::get('/organization', [OrganizationController::class, 'edit'])->name('organization.edit');
                Route::put('/organization', [OrganizationController::class, 'update'])->name('organization.update');
                Route::get('/organization/reports', [OrganizationReportController::class, 'index'])->name('organization.reports');
            });

            // Owner-Only Administrative Routes (Restricted to Owner)
            Route::middleware('business.role:owner')->group(function () {
                // Owner-only Queue Operation: Cancellation
                Route::post('/queues/{ticket}/cancel', [QueueOperationController::class, 'cancel'])->name('queues.cancel');

                // Counter Management (Milestone 13 - P1-03)
                Route::get('/counters', [CounterManagementController::class, 'index'])->name('counters.index');
                Route::post('/counters', [CounterManagementController::class, 'store'])->name('counters.store');
                Route::put('/counters/{counter}', [CounterManagementController::class, 'update'])->name('counters.update');
                Route::patch('/counters/{counter}/toggle-status', [CounterManagementController::class, 'toggleStatus'])->name('counters.toggle-status');
                Route::delete('/counters/{counter}', [CounterManagementController::class, 'destroy'])->name('counters.destroy');
                Route::post('/counters/{counter}/operators', [CounterManagementController::class, 'assignOperator'])->name('counters.assign-operator');
                Route::delete('/counters/{counter}/operators/{membership}', [CounterManagementController::class, 'unassignOperator'])->name('counters.unassign-operator');

                // Reports & Performance Analytics (Milestone 9 & Milestone 11 Exports)
                Route::get('/reports', [ReportController::class, 'index'])->name('reports');
                Route::get('/reports/export/csv', [ReportExportController::class, 'csv'])->name('reports.export.csv');
                Route::get('/reports/export/pdf', [ReportExportController::class, 'pdf'])->name('reports.export.pdf');

                // Business Profile, Operating Hours & Status Settings
                Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
                Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
                Route::delete('/settings/logo', [SettingsController::class, 'removeLogo'])->name('settings.logo.remove');
                Route::put('/settings/hours', [SettingsController::class, 'updateHours'])->name('settings.hours');
                Route::patch('/settings/status', [SettingsController::class, 'updateStatus'])->name('settings.status');
                Route::put('/settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account');

                // Services Management
                Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
                Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
                Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
                Route::patch('/services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');
                Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

                // Staff Management (Milestone 12 - P1-01 & P1-02)
                Route::get('/staff', [StaffManagementController::class, 'index'])->name('staff.index');
                Route::post('/staff/invitations', [StaffManagementController::class, 'storeInvitation'])->name('staff.invitations.store');
                Route::delete('/staff/invitations/{invitation}', [StaffManagementController::class, 'revokeInvitation'])->name('staff.invitations.revoke');
                Route::patch('/staff/{membership}/toggle-status', [StaffManagementController::class, 'toggleStatus'])->name('staff.toggle-status');
                Route::delete('/staff/{membership}', [StaffManagementController::class, 'destroy'])->name('staff.destroy');

                // Public Queue Display Management (Milestone 16 - P1-08)
                Route::get('/display', [DisplayManagementController::class, 'index'])->name('display.index');
                Route::post('/display/toggle', [DisplayManagementController::class, 'toggle'])->name('display.toggle');
                Route::post('/display/generate', [DisplayManagementController::class, 'generate'])->name('display.generate');
                Route::delete('/display/revoke', [DisplayManagementController::class, 'revoke'])->name('display.revoke');
            });
        });
    });
});

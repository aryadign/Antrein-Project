<?php

use App\Http\Controllers\Admin\BusinessCategoryController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Merchant\OnboardingController;
use App\Http\Controllers\Merchant\ServiceController;
use App\Http\Controllers\Merchant\SettingsController;
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

// Root Route: Showcase Landing & Navigation Hub
Route::get('/', function () {
    return view('preview.design-system');
});

// Customer Public Queue Route placeholder (Milestone 5 core)
Route::get('/q/{slug}', function (string $slug) {
    $business = Business::query()->where('slug', $slug)->firstOrFail();

    return view('preview.customer', [
        'business' => $business,
    ]);
})->name('customer.queue');

// Design System & Layout Preview Testbed (Milestone 2)
Route::get('/design-system', function () {
    return view('preview.design-system');
})->name('design-system');

Route::get('/preview/admin', function () {
    return view('preview.admin');
})->name('preview.admin');

Route::get('/preview/merchant', function () {
    return view('preview.merchant');
})->name('preview.merchant');

Route::get('/preview/customer', function () {
    return view('preview.customer');
})->name('preview.customer');

Route::get('/preview/auth', function () {
    return view('preview.auth');
})->name('preview.auth');

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
        Route::get('/dashboard', function () {
            return view('preview.admin');
        })->name('dashboard');

        Route::post('/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('users.deactivate');

        // Business Categories Management
        Route::get('/categories', [BusinessCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [BusinessCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [BusinessCategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/toggle-status', [BusinessCategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
        Route::delete('/categories/{category}', [BusinessCategoryController::class, 'destroy'])->name('categories.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Merchant Routes (role:merchant)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:merchant')->prefix('umkm')->name('merchant.')->group(function () {
        // Onboarding flow (Accessible before completing onboarding)
        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
        Route::post('/onboarding/step-1', [OnboardingController::class, 'storeStep1'])->name('onboarding.step1');
        Route::post('/onboarding/step-2', [OnboardingController::class, 'storeStep2'])->name('onboarding.step2');
        Route::post('/onboarding/step-3', [OnboardingController::class, 'storeStep3'])->name('onboarding.step3');
        Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
        Route::get('/onboarding/qr-download', [OnboardingController::class, 'downloadQr'])->name('onboarding.download-qr');

        // Operational merchant routes (Restricted by 'onboarded' middleware)
        Route::middleware('onboarded')->group(function () {
            Route::get('/dashboard', function () {
                return view('preview.merchant');
            })->name('dashboard');

            // Business Profile, Operating Hours & Status Settings
            Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
            Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
            Route::delete('/settings/logo', [SettingsController::class, 'removeLogo'])->name('settings.logo.remove');
            Route::put('/settings/hours', [SettingsController::class, 'updateHours'])->name('settings.hours');
            Route::patch('/settings/status', [SettingsController::class, 'updateStatus'])->name('settings.status');

            // Services Management
            Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
            Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
            Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
            Route::patch('/services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');
            Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
        });
    });
});

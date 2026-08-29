<?php

declare(strict_types=1);

use App\Http\Controllers\Platform\AuditLogController;
use App\Http\Controllers\Platform\AuthController;
use App\Http\Controllers\Platform\BillingController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\OnboardingController;
use App\Http\Controllers\Platform\SchoolController;
use App\Http\Controllers\Platform\SchoolLifecycleController;
use App\Http\Controllers\Platform\SupportAccessController;
use App\Http\Controllers\ReadinessController;
use App\Http\Controllers\PublicSignupController;
use Illuminate\Support\Facades\Route;

Route::middleware('central.domain')->group(function (): void {
    Route::get('/health/ready', ReadinessController::class)->name('health.ready');
    Route::redirect('/', '/platform');

    Route::get('/signup', [PublicSignupController::class, 'create'])->name('signup.create');
    Route::post('/signup', [PublicSignupController::class, 'store'])->middleware('throttle:5,10')->name('signup.store');
    Route::get('/signup/verify/{tenant}/{email}', [PublicSignupController::class, 'verify'])
        ->middleware(['signed', 'throttle:10,1'])
        ->where('email', '.*')
        ->name('signup.verify');

    Route::middleware('guest')->group(function (): void {
        Route::get('/platform/login', [AuthController::class, 'create'])->name('platform.login');
        Route::post('/platform/login', [AuthController::class, 'store'])->name('platform.login.store');
    });

    Route::prefix('platform')->middleware(['auth', 'platform.user'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('platform.dashboard');

        // Register static school routes before the dynamic {school} route so
        // Laravel does not resolve "create" as a school route parameter.
        Route::middleware('platform.owner')->group(function (): void {
            Route::resource('schools', SchoolController::class)->only(['create', 'store'])->names('platform.schools');
            Route::patch('/schools/{school}/billing', [BillingController::class, 'update'])->name('platform.schools.billing');
            Route::patch('/schools/{school}/lifecycle', [SchoolLifecycleController::class, 'update'])->name('platform.schools.lifecycle');
            Route::post('/schools/{school}/support-access', [SupportAccessController::class, 'store'])->name('platform.schools.support-access.store');
            Route::delete('/schools/{school}/support-access/{grant}', [SupportAccessController::class, 'destroy'])->name('platform.schools.support-access.destroy');
            Route::get('/audit-log', AuditLogController::class)->name('platform.audit');
        });

        Route::resource('schools', SchoolController::class)->only(['index', 'show'])->names('platform.schools');
        Route::post('/schools/{school}/onboarding/check', [OnboardingController::class, 'update'])->name('platform.schools.onboarding');
        Route::post('/logout', [AuthController::class, 'destroy'])->name('platform.logout');
    });
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Platform\AuthController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\SchoolController;
use Illuminate\Support\Facades\Route;

Route::middleware('central.domain')->group(function (): void {
    Route::redirect('/', '/platform');

    Route::middleware('guest')->group(function (): void {
        Route::get('/platform/login', [AuthController::class, 'create'])->name('platform.login');
        Route::post('/platform/login', [AuthController::class, 'store'])->name('platform.login.store');
    });

    Route::prefix('platform')->middleware(['auth', 'platform.user'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('platform.dashboard');
        Route::resource('schools', SchoolController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->names('platform.schools');
        Route::post('/logout', [AuthController::class, 'destroy'])->name('platform.logout');
    });
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\WasteReportController;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::prefix('auth')->name('auth.')->middleware('throttle:api-auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });

    Route::middleware(['auth:sanctum', 'role:user'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/reports', [WasteReportController::class, 'index'])->name('reports.index');
        Route::post('/reports', [WasteReportController::class, 'store'])->name('reports.store');
        Route::get('/reports/{report}', [WasteReportController::class, 'show'])->whereNumber('report')->name('reports.show');
    });
});

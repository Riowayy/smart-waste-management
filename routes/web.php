<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\Admin\WasteReportController as AdminWasteReportController;
use App\Http\Controllers\User\WasteReportController as UserWasteReportController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->role === 'admin' ? 'admin.dashboard' : 'user.dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [AdminWasteReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [AdminWasteReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [AdminWasteReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{report}', [AdminWasteReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/edit', [AdminWasteReportController::class, 'edit'])->name('reports.edit');
    Route::put('/reports/{report}', [AdminWasteReportController::class, 'update'])->name('reports.update');
    Route::patch('/reports/{report}/status', [AdminWasteReportController::class, 'updateStatus'])->name('reports.status');
    Route::delete('/reports/{report}', [AdminWasteReportController::class, 'destroy'])->name('reports.destroy');
});

Route::middleware(['auth', 'role:user'])->prefix('dashboard')->name('user.')->group(function () {
    Route::get('/', [UserDashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports/{report}', [UserWasteReportController::class, 'show'])->name('reports.show');
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
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

});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::view('/settings', 'admin.settings.index')->name('settings.index');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
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
    Route::get('/reports', [UserWasteReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [UserWasteReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [UserWasteReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{report}', [UserWasteReportController::class, 'show'])->name('reports.show');
});

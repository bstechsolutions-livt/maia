<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserPermissionController;
use App\Http\Controllers\ApiDocsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Rota para usuários inativos
Route::get('account/inactive', function () {
    return Inertia::render('auth/AccountInactive');
})->middleware(['auth'])->name('account.inactive');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'active'])
    ->name('dashboard');

Route::get('reports', [ReportsController::class, 'index'])
    ->middleware(['auth', 'verified', 'active'])
    ->name('reports');

Route::get('api-docs', [ApiDocsController::class, 'index'])
    ->middleware(['auth', 'verified', 'active'])
    ->name('api-docs');

Route::middleware(['auth', 'verified', 'active'])->prefix('admin')->name('admin.')->group(function () {
    // Permissões - requer admin.permissions.view ou admin.permissions.manage
    Route::middleware(['permission:admin.permissions.view,admin.permissions.manage'])->group(function () {
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
    });

    Route::middleware(['permission:admin.permissions.manage'])->group(function () {
        Route::get('permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
    });

    // Usuários - requer admin.users.view ou admin.users.manage
    Route::middleware(['permission:admin.users.view,admin.users.manage'])->group(function () {
        Route::get('users', [UserPermissionController::class, 'index'])->name('users.index');
        Route::get('users/{user}/permissions', [UserPermissionController::class, 'edit'])->name('users.permissions.edit');
    });

    Route::middleware(['permission:admin.users.manage'])->group(function () {
        Route::put('users/{user}/permissions', [UserPermissionController::class, 'update'])->name('users.permissions.update');
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    });
});

require __DIR__.'/settings.php';

<?php

use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'show'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [SiteController::class, 'index'])->name('dashboard');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
    Route::get('/sites/{site}', [SiteController::class, 'show'])->name('sites.show');
    Route::get('/sites/{site}/edit', [SiteController::class, 'edit'])->name('sites.edit');
    Route::put('/sites/{site}', [SiteController::class, 'update'])->name('sites.update');
    Route::delete('/sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');
    Route::post('/sites/{site}/check', [SiteController::class, 'check'])->name('sites.check');
    Route::post('/sites/{site}/discover', [SiteController::class, 'discover'])->name('sites.discover');
    Route::post('/sites/{site}/pause', [SiteController::class, 'togglePause'])->name('sites.pause');
    Route::post('/sites/{site}/pages', [SiteController::class, 'addPage'])->name('pages.store');
    Route::patch('/pages/{page}/toggle', [SiteController::class, 'togglePage'])->name('pages.toggle');
    Route::delete('/pages/{page}', [SiteController::class, 'destroyPage'])->name('pages.destroy');
    Route::get('/incidents', [SiteController::class, 'incidents'])->name('incidents');

    // Admin only: global settings and access control.
    // Per-site actions above are authorised in SiteController via App\Policies\SitePolicy.
    Route::middleware('can:admin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::resource('users', UserController::class)->except('show');
            Route::resource('roles', RoleController::class)->except('show');
            Route::resource('departments', DepartmentController::class)->except('show');
            Route::get('assignments', [AssignmentController::class, 'index'])->name('assignments.index');
            Route::put('assignments', [AssignmentController::class, 'update'])->name('assignments.update');
        });
    });
});

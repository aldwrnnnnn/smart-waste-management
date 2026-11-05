<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ReportWebController;
use App\Http\Controllers\Web\UserManagementController;
use App\Http\Controllers\Web\WebAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('user.dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register'])->middleware('throttle:10,1')->name('register.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

    // Dashboard khusus User (Pelapor)
    Route::get('/dashboard', [DashboardController::class, 'userDashboard'])
        ->middleware('role:user')
        ->name('user.dashboard');

    // Dashboard & Manajemen Pengguna khusus Admin
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');

        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });

    // Manajemen Laporan Sampah (CRUD + AI Detect Proxy)
    Route::post('/reports/detect-ai', [ReportWebController::class, 'detectAi'])
        ->middleware('throttle:30,1')
        ->name('reports.detect-ai');
    Route::patch('/reports/{report}/status', [ReportWebController::class, 'updateStatus'])
        ->middleware('role:admin')
        ->name('reports.update-status');

    Route::resource('reports', ReportWebController::class)->except(['store']);
    Route::post('/reports', [ReportWebController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('reports.store');
});

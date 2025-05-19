<?php

use Illuminate\Support\Facades\Route;
use Rais\MomoSuite\Http\Controllers\DashboardController;
use Rais\MomoSuite\Http\Controllers\AuthController;
use Rais\MomoSuite\Http\Controllers\UserController;


// Auth Routes
Route::prefix('momo')->name('momo.')->middleware('web')->group(function () {
    Route::middleware('guest.momo')->group(function () {
        Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth.momo')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // Dashboard Routes
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('transactions', [DashboardController::class, 'transactions'])->name('transactions');
        Route::get('transactions/{transaction}', [DashboardController::class, 'showTransaction'])->name('transactions.show');
        Route::post('transactions/{transaction}/check-status', [DashboardController::class, 'checkTransactionStatus'])
            ->name('transactions.check-status');
        Route::post('transactions/{transaction}/update-status', [DashboardController::class, 'updateTransactionStatus'])
            ->name('transactions.update-status');
        Route::get('users', [DashboardController::class, 'users'])->name('users');

        // User Management Routes
        Route::middleware('admin')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::put('users/update-credentials', [UserController::class, 'updateCredentials'])->name('users.update-credentials');
            Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
    });
});

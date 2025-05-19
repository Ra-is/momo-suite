<?php

use Illuminate\Support\Facades\Route;
use Rais\MomoSuite\Http\Controllers\DashboardController;
use Rais\MomoSuite\Http\Controllers\AuthController;
use Rais\MomoSuite\Http\Controllers\KorbaWebhookController;
use Rais\MomoSuite\Http\Controllers\ItcWebhookController;
use Rais\MomoSuite\Http\Controllers\HubtelWebhookController;

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
        Route::post('transactions/{transaction}/check-status', [DashboardController::class, 'checkTransactionStatus'])->name('transactions.check-status');
        Route::get('users', [DashboardController::class, 'users'])->name('users');
    });
});

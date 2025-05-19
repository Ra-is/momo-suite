<?php

use Illuminate\Support\Facades\Route;
use Rais\MomoSuite\Http\Controllers\KorbaWebhookController;
use Rais\MomoSuite\Http\Controllers\ItcWebhookController;
use Rais\MomoSuite\Http\Controllers\HubtelWebhookController;
use Rais\MomoSuite\Http\Controllers\PaystackWebhookController;

Route::prefix('api')->group(function () {
    Route::prefix('webhook')->group(function () {
        Route::post('korba', [KorbaWebhookController::class, 'handle'])->name('momo.webhook.korba');
        Route::post('itc', [ItcWebhookController::class, 'handle'])->name('momo.webhook.itc');
        Route::post('hubtel', [HubtelWebhookController::class, 'handle'])->name('momo.webhook.hubtel');
        Route::post('paystack', [PaystackWebhookController::class, 'handle'])->name('momo.webhook.paystack');
    });
});

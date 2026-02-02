<?php

use Illuminate\Support\Facades\Route;
use Kezi\Purchase\Http\Controllers\PurchaseController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('purchases', PurchaseController::class)->names('purchase');
});

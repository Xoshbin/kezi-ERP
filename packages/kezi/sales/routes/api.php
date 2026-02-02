<?php

use Illuminate\Support\Facades\Route;
use Kezi\Sales\Http\Controllers\SalesController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('sales', SalesController::class)->names('sales');
});

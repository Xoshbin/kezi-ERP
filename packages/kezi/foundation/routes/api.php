<?php

use Illuminate\Support\Facades\Route;
use Kezi\Foundation\Http\Controllers\FoundationController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('foundations', FoundationController::class)->names('foundation');
});

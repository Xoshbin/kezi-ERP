<?php

use Illuminate\Support\Facades\Route;
use Kezi\HR\Http\Controllers\HRController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('hrs', HRController::class)->names('hr');
});

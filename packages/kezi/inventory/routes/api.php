<?php

use Illuminate\Support\Facades\Route;
use Kezi\Inventory\Http\Controllers\InventoryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('inventories', InventoryController::class)->names('inventory');
});

<?php

use Illuminate\Support\Facades\Route;
use Kezi\Inventory\Http\Controllers\InventoryController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('inventories', InventoryController::class)->names('inventory');
});

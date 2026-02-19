<?php

use Illuminate\Support\Facades\Route;
use Kezi\Pos\Http\Controllers\Api\MasterDataSyncController;
use Kezi\Pos\Http\Controllers\Api\OrderSyncController;
use Kezi\Pos\Http\Controllers\Api\SessionController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->group(function () {
        // Master Data Sync
        Route::get('/sync/master-data', [MasterDataSyncController::class, 'index'])->name('sync.master-data');

        // Order Sync
        Route::post('/sync/orders', [OrderSyncController::class, 'store'])->name('sync.orders');

        // Session Management
        Route::post('/sessions/open', [SessionController::class, 'open'])->name('sessions.open');
        Route::post('/sessions/{session}/close', [SessionController::class, 'close'])->name('sessions.close');
        Route::get('/sessions/current', [SessionController::class, 'current'])->name('sessions.current');
    });

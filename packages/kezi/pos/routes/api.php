<?php

use Illuminate\Support\Facades\Route;
use Kezi\Pos\Http\Controllers\Api\MasterDataSyncController;
use Kezi\Pos\Http\Controllers\Api\OrderSyncController;
use Kezi\Pos\Http\Controllers\Api\SessionController;

Route::middleware(['auth:sanctum'])
    ->group(function () {
        // Master Data Sync
        Route::get('/sync/master-data', [MasterDataSyncController::class, 'index']);

        // Order Sync
        Route::post('/sync/orders', [OrderSyncController::class, 'store']);

        // Session Management
        Route::post('/sessions/open', [SessionController::class, 'open']);
        Route::post('/sessions/{session}/close', [SessionController::class, 'close']);
        Route::get('/sessions/current', [SessionController::class, 'current']);
    });

<?php

use Illuminate\Support\Facades\Route;
use Kezi\Pos\Http\Controllers\Api\MasterDataSyncController;
use Kezi\Pos\Http\Controllers\Api\OrderSyncController;
use Kezi\Pos\Http\Controllers\Api\SessionController;

Route::middleware(['auth', 'throttle:60,1'])
    ->group(function () {
        // Master Data Sync
        Route::get('/sync/master-data', [MasterDataSyncController::class, 'index'])->name('sync.master-data');

        // Session Management
        Route::post('/sessions/open', [SessionController::class, 'open'])->name('sessions.open');
        Route::post('/sessions/{session}/close', [SessionController::class, 'close'])->name('sessions.close');
        Route::get('/sessions/current', [SessionController::class, 'current'])->name('sessions.current');

        // POS Order Search & Returns
        Route::post('/orders/search', [\Kezi\Pos\Http\Controllers\PosOrderSearchController::class, 'search'])->name('orders.search');
        Route::get('/orders/quick-search', [\Kezi\Pos\Http\Controllers\PosOrderSearchController::class, 'quickSearch'])->name('orders.quick-search');
        Route::get('/orders/{order}/details', [\Kezi\Pos\Http\Controllers\PosOrderSearchController::class, 'details'])->name('orders.details');
        Route::get('/orders/{order}/return-eligibility', [\Kezi\Pos\Http\Controllers\PosOrderSearchController::class, 'checkReturnEligibility'])->name('orders.return-eligibility');
    });

// Order sync gets a higher throttle — batch uploads from offline POS sessions can be large.
Route::middleware(['auth', 'throttle:600,1'])
    ->group(function () {
        Route::post('/sync/orders', [OrderSyncController::class, 'store'])->name('sync.orders');
    });

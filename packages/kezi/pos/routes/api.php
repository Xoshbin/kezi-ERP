<?php

use Illuminate\Support\Facades\Route;
use Kezi\Pos\Http\Controllers\Api\MasterDataSyncController;
use Kezi\Pos\Http\Controllers\Api\OrderSyncController;
use Kezi\Pos\Http\Controllers\Api\SessionController;

Route::middleware(['auth:sanctum', 'throttle:60,1'])
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

        // POS Returns
        Route::post('/returns', [\Kezi\Pos\Http\Controllers\Api\PosReturnController::class, 'store'])->name('returns.store');
        Route::post('/returns/{return}/submit', [\Kezi\Pos\Http\Controllers\Api\PosReturnController::class, 'submit'])->name('returns.submit');
        Route::post('/returns/{return}/approve', [\Kezi\Pos\Http\Controllers\Api\PosReturnController::class, 'approve'])->name('returns.approve');
        Route::post('/returns/{return}/reject', [\Kezi\Pos\Http\Controllers\Api\PosReturnController::class, 'reject'])->name('returns.reject');
        Route::post('/returns/{return}/process', [\Kezi\Pos\Http\Controllers\Api\PosReturnController::class, 'process'])->name('returns.process');

        // Manager PIN verification — approve a return at the terminal without manager login
        Route::post('/returns/{return}/verify-pin', [\Kezi\Pos\Http\Controllers\Api\ManagerPinController::class, 'verifyAndApprove'])->name('returns.verify-pin');

    });

// Order sync gets a higher throttle — batch uploads from offline POS sessions can be large.
Route::middleware(['auth:sanctum', 'throttle:600,1'])
    ->group(function () {
        Route::post('/sync/orders', [OrderSyncController::class, 'store'])->name('sync.orders');
    });

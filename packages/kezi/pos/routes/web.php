<?php

use Illuminate\Support\Facades\Route;
use Kezi\Pos\Http\Controllers\PosTerminalController;

Route::middleware(['web', 'auth', \App\Http\Middleware\RestrictPosOnlyUser::class])
    ->group(function () {
        Route::get('/pos', PosTerminalController::class)->name('pos.terminal');
    });

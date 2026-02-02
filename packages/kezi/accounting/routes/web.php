<?php

use Illuminate\Support\Facades\Route;
use Kezi\Accounting\Http\Controllers\AccountingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('accountings', AccountingController::class)->names('accounting');
});

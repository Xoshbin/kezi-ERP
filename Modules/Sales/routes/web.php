<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\SalesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('sales', SalesController::class)->names('sales');
});

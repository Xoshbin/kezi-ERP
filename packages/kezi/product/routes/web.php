<?php

use Illuminate\Support\Facades\Route;
use Kezi\Product\Http\Controllers\ProductController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('products', ProductController::class)->names('product');
});

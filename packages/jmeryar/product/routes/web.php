<?php

use Illuminate\Support\Facades\Route;
use Jmeryar\Product\Http\Controllers\ProductController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('products', ProductController::class)->names('product');
});

<?php

use Illuminate\Support\Facades\Route;
use Jmeryar\Product\Http\Controllers\ProductController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('products', ProductController::class)->names('product');
});

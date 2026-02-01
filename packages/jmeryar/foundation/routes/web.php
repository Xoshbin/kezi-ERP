<?php

use Illuminate\Support\Facades\Route;
use Jmeryar\Foundation\Http\Controllers\FoundationController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('foundations', FoundationController::class)->names('foundation');
});

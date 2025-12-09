<?php

use Illuminate\Support\Facades\Route;
use Modules\Foundation\Http\Controllers\FoundationController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('foundations', FoundationController::class)->names('foundation');
});

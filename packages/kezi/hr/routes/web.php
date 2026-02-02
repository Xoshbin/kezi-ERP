<?php

use Illuminate\Support\Facades\Route;
use Kezi\HR\Http\Controllers\HRController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('hrs', HRController::class)->names('hr');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\HR\Http\Controllers\HRController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('hrs', HRController::class)->names('hr');
});

<?php

use Illuminate\Support\Facades\Route;

// Minimal docs route used by tests in reconciliation views
Route::get('/docs/payments', function () {
    return response('Payments docs placeholder', 200);
})->name('docs.payments');


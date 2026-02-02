<?php

use Illuminate\Support\Facades\Route;
use Kezi\Payment\Http\Controllers\ChequeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/cheques/{cheque}/print', [ChequeController::class, 'print'])->name('cheques.print');
});

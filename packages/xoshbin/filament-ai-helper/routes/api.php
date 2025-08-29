<?php

use Xoshbin\FilamentAiHelper\Http\Controllers\AiChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AI Helper API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the AI Helper plugin. These routes are
| automatically loaded by the FilamentAiHelperServiceProvider.
|
*/

Route::middleware(['web', 'auth'])->prefix('api/ai-helper')->group(function () {
    Route::post('/chat', [AiChatController::class, 'chat'])->name('ai-helper.chat');
});

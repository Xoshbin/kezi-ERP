<?php

use App\Exceptions\Inventory\InsufficientCostInformationException;
use App\Http\Middleware\SetLocaleFromSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\RestrictPosOnlyUser::class,
            SetLocaleFromSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('DEBUG_EXCEPTION: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        });
        $exceptions->render(function (InsufficientCostInformationException $e, \Illuminate\Http\Request $request) {
            // If this is a Livewire request (Filament uses Livewire), handle it gracefully
            if ($request->header('X-Livewire') || $request->wantsJson()) {
                return response()->json([
                    'message' => $e->getUserFriendlyMessage(),
                    'error_type' => 'cost_validation_error',
                    'error_data' => $e->getUserFriendlyErrorData(),
                ], 422);
            }

            // For regular web requests, redirect back with error
            return redirect()->back()->withErrors([
                'cost_validation' => $e->getUserFriendlyMessage(),
            ])->withInput();
        });
    })->create();

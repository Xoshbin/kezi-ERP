<?php

namespace AccounTech\FilamentAiHelper;

use AccounTech\FilamentAiHelper\Actions\GetAIAssistantResponseAction;
use AccounTech\FilamentAiHelper\Services\GeminiService;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FilamentAiHelperServiceProvider extends ServiceProvider
{
    public static string $name = 'filament-ai-helper';

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/filament-ai-helper.php',
            'filament-ai-helper'
        );

        // Register services in the container
        $this->app->singleton(GeminiService::class, function ($app) {
            return new GeminiService(
                apiKey: config('filament-ai-helper.gemini.api_key'),
                apiUrl: config('filament-ai-helper.gemini.api_url'),
                timeout: config('filament-ai-helper.gemini.timeout'),
                maxRetries: config('filament-ai-helper.gemini.max_retries')
            );
        });

        $this->app->bind(GetAIAssistantResponseAction::class);
    }

    public function boot(): void
    {
        // Register Livewire components
        Livewire::component('ai-chat-box', \AccounTech\FilamentAiHelper\Livewire\AiChatBox::class);

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-ai-helper');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-ai-helper');

        // Register assets
        FilamentAsset::register([
            Css::make('filament-ai-helper-styles', __DIR__ . '/../resources/dist/filament-ai-helper.css'),
            Js::make('filament-ai-helper-scripts', __DIR__ . '/../resources/dist/filament-ai-helper.js'),
        ], package: 'accountech/filament-ai-helper');

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/filament-ai-helper.php' => config_path('filament-ai-helper.php'),
            ], 'filament-ai-helper-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/filament-ai-helper'),
            ], 'filament-ai-helper-views');

            $this->publishes([
                __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/filament-ai-helper'),
            ], 'filament-ai-helper-translations');
        }

        // Register the plugin with Filament panels
        $this->registerFilamentPlugin();
    }

    protected function registerFilamentPlugin(): void
    {
        // AI Helper is integrated via HasAiHelper trait in individual resource pages
        // No global render hooks needed
    }
}

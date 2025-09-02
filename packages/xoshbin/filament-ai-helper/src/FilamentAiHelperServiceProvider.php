<?php

namespace Xoshbin\FilamentAiHelper;

use Xoshbin\FilamentAiHelper\Actions\GetAIAssistantResponseAction;
use Xoshbin\FilamentAiHelper\Actions\FillFormAction;
use Xoshbin\FilamentAiHelper\Actions\UpdateFormAction;
use Xoshbin\FilamentAiHelper\Services\GeminiService;
use Xoshbin\FilamentAiHelper\Services\DeepContextService;
use Xoshbin\FilamentAiHelper\Services\FormSchemaExtractor;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
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
        $this->app->bind(FillFormAction::class);
        $this->app->bind(UpdateFormAction::class);
        $this->app->singleton(DeepContextService::class);
        $this->app->singleton(FormSchemaExtractor::class);
    }

    public function boot(): void
    {
        // Respect global enable/disable config
        if (!config('filament-ai-helper.enabled', true)) {
            return;
        }

        // Register Livewire components
        Livewire::component('ai-chat-box', \Xoshbin\FilamentAiHelper\Livewire\AiChatBox::class);
        Livewire::component('ai-chat-widget', \Xoshbin\FilamentAiHelper\Livewire\AiChatWidget::class);

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-ai-helper');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-ai-helper');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Register assets - use published assets if available, fallback to package assets
        $cssPath = public_path('vendor/filament-ai-helper/filament-ai-helper.css');
        $jsPath = public_path('vendor/filament-ai-helper/filament-ai-helper.js');

        FilamentAsset::register([
            Css::make('filament-ai-helper-styles', file_exists($cssPath)
                ? asset('vendor/filament-ai-helper/filament-ai-helper.css')
                : __DIR__ . '/../resources/dist/filament-ai-helper.css'),
            Js::make('filament-ai-helper-scripts', file_exists($jsPath)
                ? asset('vendor/filament-ai-helper/filament-ai-helper.js')
                : __DIR__ . '/../resources/dist/filament-ai-helper.js'),
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

            $this->publishes([
                __DIR__ . '/../resources/dist' => public_path('vendor/filament-ai-helper'),
            ], 'filament-ai-helper-assets');
        }

        // Register the plugin with Filament panels (if auto-registration is enabled)
        if (config('filament-ai-helper.auto_register', true)) {
            $this->registerFilamentPlugin();
        }
    }

    protected function registerFilamentPlugin(): void
    {
        // Register render hook to include simple chat widget on all Filament pages
        FilamentView::registerRenderHook(
            'panels::body.end',
            fn (): string => view('filament-ai-helper::chat-widget')->render()
        );
    }
}

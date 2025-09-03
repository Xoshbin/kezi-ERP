<?php

namespace Xoshbin\FilamentAiHelper\Tests;

use Filament\FilamentServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Xoshbin\FilamentAiHelper\FilamentAiHelperServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filament-ai-helper.gemini.api_key', 'test-api-key');
        config()->set('filament-ai-helper.cache.enabled', false);
        config()->set('filament-ai-helper.security.log_requests', false);
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentAiHelperServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up basic Filament configuration
        config()->set('filament.default', 'admin');
        config()->set('filament.panels.admin', [
            'id' => 'admin',
            'path' => 'admin',
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
    }
}

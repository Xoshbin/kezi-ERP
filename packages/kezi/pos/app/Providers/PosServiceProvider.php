<?php

namespace Kezi\Pos\Providers;

use Illuminate\Support\ServiceProvider;

class PosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'pos');
    }
}

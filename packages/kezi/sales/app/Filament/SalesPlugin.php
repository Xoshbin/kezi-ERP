<?php

namespace Kezi\Sales\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class SalesPlugin implements Plugin
{
    public function getId(): string
    {
        return 'sales';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__, for: 'Kezi\\Sales\\Filament')
            ->discoverPages(in: __DIR__, for: 'Kezi\\Sales\\Filament')
            ->discoverClusters(in: __DIR__.'/Clusters', for: 'Kezi\\Sales\\Filament\\Clusters');
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return new static;
    }
}

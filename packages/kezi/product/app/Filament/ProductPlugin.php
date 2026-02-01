<?php

namespace Kezi\Product\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class ProductPlugin implements Plugin
{
    public function getId(): string
    {
        return 'product';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__, for: 'Kezi\\Product\\Filament')
            ->discoverPages(in: __DIR__, for: 'Kezi\\Product\\Filament')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Kezi\\Product\\Filament\\Clusters');
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

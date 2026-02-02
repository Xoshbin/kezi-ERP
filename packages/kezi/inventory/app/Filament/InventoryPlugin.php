<?php

namespace Kezi\Inventory\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class InventoryPlugin implements Plugin
{
    public function getId(): string
    {
        return 'inventory';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__, for: 'Kezi\\Inventory\\Filament')
            ->discoverPages(in: __DIR__, for: 'Kezi\\Inventory\\Filament')
            ->discoverClusters(in: __DIR__.'/Clusters', for: 'Kezi\\Inventory\\Filament\\Clusters');
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

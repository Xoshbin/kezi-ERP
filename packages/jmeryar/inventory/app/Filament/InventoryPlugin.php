<?php

namespace Jmeryar\Inventory\Filament;

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
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Jmeryar\\Inventory\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'Jmeryar\\Inventory\\Filament\\Pages')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Jmeryar\\Inventory\\Filament\\Clusters');
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

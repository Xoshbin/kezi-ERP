<?php

namespace Modules\Inventory\Filament;

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
            ->discoverResources(in: base_path('Modules/Inventory/app/Filament/Resources'), for: 'Modules\\Inventory\\Filament\\Resources')
            ->discoverPages(in: base_path('Modules/Inventory/app/Filament/Pages'), for: 'Modules\\Inventory\\Filament\\Pages')
            ->discoverClusters(in: base_path('Modules/Inventory/app/Filament/Clusters'), for: 'Modules\\Inventory\\Filament\\Clusters');
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

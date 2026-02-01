<?php

namespace Modules\Product\Filament;

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
            ->discoverResources(in: base_path('Modules/Product/app/Filament/Resources'), for: 'Modules\\Product\\Filament\\Resources')
            ->discoverPages(in: base_path('Modules/Product/app/Filament/Pages'), for: 'Modules\\Product\\Filament\\Pages')
            ->discoverClusters(in: base_path('Modules/Product/app/Filament/Clusters'), for: 'Modules\\Product\\Filament\\Clusters');
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

<?php

namespace Modules\Sales\Filament;

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
            ->discoverResources(in: base_path('Modules/Sales/app/Filament/Resources'), for: 'Modules\\Sales\\Filament\\Resources')
            ->discoverPages(in: base_path('Modules/Sales/app/Filament/Pages'), for: 'Modules\\Sales\\Filament\\Pages')
            ->discoverClusters(in: base_path('Modules/Sales/app/Filament/Clusters'), for: 'Modules\\Sales\\Filament\\Clusters');
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

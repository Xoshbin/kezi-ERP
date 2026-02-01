<?php

namespace Modules\Manufacturing\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class ManufacturingPlugin implements Plugin
{
    public function getId(): string
    {
        return 'manufacturing';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: base_path('Modules/Manufacturing/app/Filament/Resources'), for: 'Modules\\Manufacturing\\Filament\\Resources')
            ->discoverPages(in: base_path('Modules/Manufacturing/app/Filament/Pages'), for: 'Modules\\Manufacturing\\Filament\\Pages')
            ->discoverClusters(in: base_path('Modules/Manufacturing/app/Filament/Clusters'), for: 'Modules\\Manufacturing\\Filament\\Clusters');
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

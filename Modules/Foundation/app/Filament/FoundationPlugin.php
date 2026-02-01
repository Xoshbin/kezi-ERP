<?php

namespace Modules\Foundation\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FoundationPlugin implements Plugin
{
    public function getId(): string
    {
        return 'foundation';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: base_path('Modules/Foundation/app/Filament/Resources'), for: 'Modules\\Foundation\\Filament\\Resources')
            ->discoverPages(in: base_path('Modules/Foundation/app/Filament/Pages'), for: 'Modules\\Foundation\\Filament\\Pages')
            ->discoverClusters(in: base_path('Modules/Foundation/app/Filament/Clusters'), for: 'Modules\\Foundation\\Filament\\Clusters');
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

<?php

namespace Kezi\Manufacturing\Filament;

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
            ->discoverResources(in: __DIR__, for: 'Kezi\\Manufacturing\\Filament')
            ->discoverPages(in: __DIR__, for: 'Kezi\\Manufacturing\\Filament')
            ->discoverClusters(in: __DIR__.'/Clusters', for: 'Kezi\\Manufacturing\\Filament\\Clusters');
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

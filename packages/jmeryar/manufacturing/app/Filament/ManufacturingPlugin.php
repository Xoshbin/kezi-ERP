<?php

namespace Jmeryar\Manufacturing\Filament;

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
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Jmeryar\\Manufacturing\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'Jmeryar\\Manufacturing\\Filament\\Pages')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Jmeryar\\Manufacturing\\Filament\\Clusters');
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

<?php

namespace Jmeryar\Sales\Filament;

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
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Jmeryar\\Sales\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'Jmeryar\\Sales\\Filament\\Pages')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Jmeryar\\Sales\\Filament\\Clusters');
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

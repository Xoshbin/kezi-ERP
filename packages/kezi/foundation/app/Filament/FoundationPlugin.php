<?php

namespace Kezi\Foundation\Filament;

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
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Kezi\\Foundation\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'Kezi\\Foundation\\Filament\\Pages')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Kezi\\Foundation\\Filament\\Clusters');
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

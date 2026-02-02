<?php

namespace Kezi\HR\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class HRPlugin implements Plugin
{
    public function getId(): string
    {
        return 'hr';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__, for: 'Kezi\\HR\\Filament')
            ->discoverPages(in: __DIR__, for: 'Kezi\\HR\\Filament')
            ->discoverClusters(in: __DIR__.'/Clusters', for: 'Kezi\\HR\\Filament\\Clusters');
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

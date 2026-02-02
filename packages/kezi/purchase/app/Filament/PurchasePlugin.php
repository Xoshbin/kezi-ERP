<?php

namespace Kezi\Purchase\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PurchasePlugin implements Plugin
{
    public function getId(): string
    {
        return 'purchase';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__, for: 'Kezi\\Purchase\\Filament')
            ->discoverPages(in: __DIR__, for: 'Kezi\\Purchase\\Filament')
            ->discoverClusters(in: __DIR__.'/Clusters', for: 'Kezi\\Purchase\\Filament\\Clusters');
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

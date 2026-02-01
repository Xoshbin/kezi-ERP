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
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Kezi\\Purchase\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'Kezi\\Purchase\\Filament\\Pages')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Kezi\\Purchase\\Filament\\Clusters');
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

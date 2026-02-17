<?php

namespace Kezi\Pos\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PosPlugin implements Plugin
{
    public function getId(): string
    {
        return 'pos';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__.'/Clusters/Pos/Resources', for: 'Kezi\\Pos\\Filament\\Clusters\\Pos\\Resources')
            ->discoverPages(in: __DIR__.'/Clusters/Pos/Pages', for: 'Kezi\\Pos\\Filament\\Clusters\\Pos\\Pages')
            ->discoverWidgets(in: __DIR__.'/Clusters/Pos/Widgets', for: 'Kezi\\Pos\\Filament\\Clusters\\Pos\\Widgets')
            ->discoverClusters(in: __DIR__.'/Clusters', for: 'Kezi\\Pos\\Filament\\Clusters');
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

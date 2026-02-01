<?php

namespace Kezi\QualityControl\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class QualityControlPlugin implements Plugin
{
    public function getId(): string
    {
        return 'quality-control';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Kezi\\QualityControl\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'Kezi\\QualityControl\\Filament\\Pages')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Kezi\\QualityControl\\Filament\\Clusters');
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

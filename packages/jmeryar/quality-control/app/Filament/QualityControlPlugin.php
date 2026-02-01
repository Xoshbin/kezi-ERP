<?php

namespace Jmeryar\QualityControl\Filament;

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
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Jmeryar\\QualityControl\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'Jmeryar\\QualityControl\\Filament\\Pages')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Jmeryar\\QualityControl\\Filament\\Clusters');
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

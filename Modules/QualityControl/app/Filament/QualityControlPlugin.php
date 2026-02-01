<?php

namespace Modules\QualityControl\Filament;

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
            ->discoverResources(in: base_path('Modules/QualityControl/app/Filament/Resources'), for: 'Modules\\QualityControl\\Filament\\Resources')
            ->discoverPages(in: base_path('Modules/QualityControl/app/Filament/Pages'), for: 'Modules\\QualityControl\\Filament\\Pages')
            ->discoverClusters(in: base_path('Modules/QualityControl/app/Filament/Clusters'), for: 'Modules\\QualityControl\\Filament\\Clusters');
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

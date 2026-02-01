<?php

namespace Modules\HR\Filament;

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
            ->discoverResources(in: base_path('Modules/HR/app/Filament/Resources'), for: 'Modules\\HR\\Filament\\Resources')
            ->discoverPages(in: base_path('Modules/HR/app/Filament/Pages'), for: 'Modules\\HR\\Filament\\Pages')
            ->discoverClusters(in: base_path('Modules/HR/app/Filament/Clusters'), for: 'Modules\\HR\\Filament\\Clusters');
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

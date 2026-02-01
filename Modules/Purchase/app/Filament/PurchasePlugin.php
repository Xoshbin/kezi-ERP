<?php

namespace Modules\Purchase\Filament;

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
            ->discoverResources(in: base_path('Modules/Purchase/app/Filament/Resources'), for: 'Modules\\Purchase\\Filament\\Resources')
            ->discoverPages(in: base_path('Modules/Purchase/app/Filament/Pages'), for: 'Modules\\Purchase\\Filament\\Pages')
            ->discoverClusters(in: base_path('Modules/Purchase/app/Filament/Clusters'), for: 'Modules\\Purchase\\Filament\\Clusters');
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

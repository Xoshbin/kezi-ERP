<?php

namespace Modules\Payment\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PaymentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'payment';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: base_path('Modules/Payment/app/Filament/Resources'), for: 'Modules\\Payment\\Filament\\Resources')
            ->discoverPages(in: base_path('Modules/Payment/app/Filament/Pages'), for: 'Modules\\Payment\\Filament\\Pages')
            ->discoverClusters(in: base_path('Modules/Payment/app/Filament/Clusters'), for: 'Modules\\Payment\\Filament\\Clusters');
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

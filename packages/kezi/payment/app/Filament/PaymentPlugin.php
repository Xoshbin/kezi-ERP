<?php

namespace Kezi\Payment\Filament;

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
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Kezi\\Payment\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'Kezi\\Payment\\Filament\\Pages')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Kezi\\Payment\\Filament\\Clusters');
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

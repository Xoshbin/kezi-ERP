<?php

namespace Jmeryar\Payment\Filament;

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
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Jmeryar\\Payment\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'Jmeryar\\Payment\\Filament\\Pages')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Jmeryar\\Payment\\Filament\\Clusters');
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

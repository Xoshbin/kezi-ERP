<?php

namespace Kezi\Accounting\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

final class AccountingPlugin implements Plugin
{
    public function getId(): string
    {
        return 'accounting';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__, for: 'Kezi\\Accounting\\Filament')
            ->discoverPages(in: __DIR__, for: 'Kezi\\Accounting\\Filament')
            ->discoverClusters(in: __DIR__.'/Clusters', for: 'Kezi\\Accounting\\Filament\\Clusters');
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return new self;
    }
}

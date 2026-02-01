<?php

namespace Modules\Accounting\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class AccountingPlugin implements Plugin
{
    public function getId(): string
    {
        return 'accounting';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: base_path('Modules/Accounting/app/Filament/Resources'), for: 'Modules\\Accounting\\Filament\\Resources')
            ->discoverPages(in: base_path('Modules/Accounting/app/Filament/Pages'), for: 'Modules\\Accounting\\Filament\\Pages')
            ->discoverClusters(in: base_path('Modules/Accounting/app/Filament/Clusters'), for: 'Modules\\Accounting\\Filament\\Clusters');
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

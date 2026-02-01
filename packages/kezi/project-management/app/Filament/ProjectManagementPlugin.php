<?php

namespace Kezi\ProjectManagement\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class ProjectManagementPlugin implements Plugin
{
    public function getId(): string
    {
        return 'project-management';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Kezi\\ProjectManagement\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'Kezi\\ProjectManagement\\Filament\\Pages')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Kezi\\ProjectManagement\\Filament\\Clusters');
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

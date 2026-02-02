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
            ->discoverResources(in: __DIR__, for: 'Kezi\\ProjectManagement\\Filament')
            ->discoverPages(in: __DIR__, for: 'Kezi\\ProjectManagement\\Filament')
            ->discoverClusters(in: __DIR__.'/Clusters', for: 'Kezi\\ProjectManagement\\Filament\\Clusters');
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

<?php

namespace Jmeryar\ProjectManagement\Filament;

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
            ->discoverResources(in: __DIR__ . '/Resources', for: 'Jmeryar\\ProjectManagement\\Filament\\Resources')
            ->discoverPages(in: __DIR__ . '/Pages', for: 'Jmeryar\\ProjectManagement\\Filament\\Pages')
            ->discoverClusters(in: __DIR__ . '/Clusters', for: 'Jmeryar\\ProjectManagement\\Filament\\Clusters');
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

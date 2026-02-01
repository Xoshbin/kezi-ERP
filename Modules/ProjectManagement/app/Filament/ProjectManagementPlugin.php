<?php

namespace Modules\ProjectManagement\Filament;

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
            ->discoverResources(in: base_path('Modules/ProjectManagement/app/Filament/Resources'), for: 'Modules\\ProjectManagement\\Filament\\Resources')
            ->discoverPages(in: base_path('Modules/ProjectManagement/app/Filament/Pages'), for: 'Modules\\ProjectManagement\\Filament\\Pages')
            ->discoverClusters(in: base_path('Modules/ProjectManagement/app/Filament/Clusters'), for: 'Modules\\ProjectManagement\\Filament\\Clusters');
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

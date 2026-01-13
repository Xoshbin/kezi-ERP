<?php

namespace Modules\ProjectManagement\Filament\Clusters\ProjectManagement;

use BackedEnum;
use Filament\Clusters\Cluster;

class ProjectManagementCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('projectmanagement::project.navigation.label');
    }

    public static function getClusterLabel(): string
    {
        return __('projectmanagement::project.navigation.label');
    }
}

<?php

namespace App\Filament\Clusters\Settings;

use BackedEnum;
use Filament\Clusters\Cluster;

class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static bool $shouldRegisterSubNavigation = false;

    protected static ?int $navigationSort = 100;

    public static function getNavigationLabel(): string
    {
        return __('foundation::navigation.clusters.settings');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('foundation::navigation.clusters.settings');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}

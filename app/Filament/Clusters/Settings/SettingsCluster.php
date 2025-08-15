<?php

namespace App\Filament\Clusters\Settings;

use Filament\Clusters\Cluster;

class SettingsCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 100;

    public static function getNavigationLabel(): string
    {
        return __('navigation.clusters.settings');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('navigation.clusters.settings');
    }
}

<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Inventory extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return __('navigation.clusters.inventory');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('navigation.clusters.inventory');
    }
}

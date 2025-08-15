<?php

namespace App\Filament\Clusters\Inventory;

use Filament\Clusters\Cluster;

class InventoryCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';

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

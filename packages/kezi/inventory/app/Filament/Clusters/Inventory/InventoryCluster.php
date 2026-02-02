<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory;

use BackedEnum;
use Filament\Clusters\Cluster;

class InventoryCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static bool $shouldRegisterSubNavigation = false;

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return __('inventory::navigation.clusters.inventory');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}

<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing;

use Filament\Clusters\Cluster;

class ManufacturingCluster extends Cluster
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function getNavigationLabel(): string
    {
        return __('manufacturing::cluster.navigation.name');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}

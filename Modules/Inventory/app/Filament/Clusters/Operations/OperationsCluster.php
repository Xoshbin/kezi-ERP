<?php

namespace Modules\Inventory\Filament\Clusters\Operations;

use Filament\Clusters\Cluster;

class OperationsCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    public static function getNavigationLabel(): string
    {
        return __('navigation.clusters.operations');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}

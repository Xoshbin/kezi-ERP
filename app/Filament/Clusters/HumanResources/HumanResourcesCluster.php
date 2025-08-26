<?php

namespace App\Filament\Clusters\HumanResources;

use BackedEnum;
use Filament\Clusters\Cluster;

class HumanResourcesCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return __('navigation.clusters.human_resources');
    }
}

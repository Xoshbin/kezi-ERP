<?php

namespace App\Filament\Clusters\Accounting;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class AccountingCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function getNavigationLabel(): string
    {
        return __('navigation.clusters.accounting');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('navigation.clusters.accounting');
    }
}

<?php

namespace App\Filament\Clusters\Accounting;

use Filament\Clusters\Cluster;

class AccountingCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    public static function getNavigationLabel(): string
    {
        return __('navigation.clusters.accounting');
    }
}

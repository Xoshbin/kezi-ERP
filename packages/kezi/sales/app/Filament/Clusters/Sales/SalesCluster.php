<?php

namespace Kezi\Sales\Filament\Clusters\Sales;

use BackedEnum;
use Filament\Clusters\Cluster;

class SalesCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('sales::navigation.sales');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}

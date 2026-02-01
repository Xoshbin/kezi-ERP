<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases;

use BackedEnum;
use Filament\Clusters\Cluster;

class PurchasesCluster extends Cluster
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    public static function getNavigationLabel(): string
    {
        return __('purchase::purchases.navigation.label');
    }
}

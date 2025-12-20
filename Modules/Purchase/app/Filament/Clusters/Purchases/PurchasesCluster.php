<?php

namespace Modules\Purchase\Filament\Clusters\Purchases;

use BackedEnum;
use Filament\Clusters\Cluster;

class PurchasesCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Purchases';

    protected static ?string $slug = 'purchases';

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('purchase::purchases.navigation.label');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}

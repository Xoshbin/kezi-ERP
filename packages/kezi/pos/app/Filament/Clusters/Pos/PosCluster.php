<?php

namespace Kezi\Pos\Filament\Clusters\Pos;

use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class PosCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::CreditCard;

    public static function getNavigationLabel(): string
    {
        return __('pos::navigation.pos');
    }
}

<?php

namespace Jmeryar\Product\Filament\Clusters\Product;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class ProductCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}

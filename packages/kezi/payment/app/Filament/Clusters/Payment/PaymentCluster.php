<?php

namespace Kezi\Payment\Filament\Clusters\Payment;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class PaymentCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}

<?php

namespace App\Filament\Clusters\Accounting\Clusters\AccountingReports;

use App\Filament\Clusters\Accounting\AccountingCluster;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class AccountingReportsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $cluster = AccountingCluster::class;

}

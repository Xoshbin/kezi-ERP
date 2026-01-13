<?php

namespace Modules\Accounting\Filament\Clusters\Reporting;

use Filament\Clusters\Cluster;

class ReportingCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    public static function getNavigationLabel(): string
    {
        return __('navigation.clusters.reporting');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}

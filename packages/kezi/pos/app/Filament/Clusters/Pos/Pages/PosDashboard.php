<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Pages;

use Filament\Pages\Dashboard;
use Kezi\Pos\Filament\Clusters\Pos\PosCluster;
use Kezi\Pos\Filament\Clusters\Pos\Widgets\PosSalesTrendChart;
use Kezi\Pos\Filament\Clusters\Pos\Widgets\PosStatsOverviewWidget;

class PosDashboard extends Dashboard
{
    protected static ?string $cluster = PosCluster::class;

    protected static string $routePath = 'pos-dashboard';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $title = 'POS Overview';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            PosStatsOverviewWidget::class,
            PosSalesTrendChart::class,
        ];
    }
}

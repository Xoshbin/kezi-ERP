<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Pages;

use Filament\Pages\Page;
use Kezi\Pos\Filament\Clusters\Pos\PosCluster;

class PosTerminal extends Page
{
    protected static ?string $cluster = PosCluster::class;

    protected static string $view = 'pos::filament.clusters.pos.pages.pos-terminal';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    public static function getNavigationLabel(): string
    {
        return __('POS Terminal');
    }

    public function getHeader(): null
    {
        return null; // Empty header to maximize space
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}

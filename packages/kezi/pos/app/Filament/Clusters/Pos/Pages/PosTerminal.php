<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Pages;

use Filament\Pages\Page;
use Kezi\Pos\Filament\Clusters\Pos\PosCluster;

class PosTerminal extends Page
{
    protected static ?string $cluster = PosCluster::class;

    protected string $view = 'pos::filament.clusters.pos.pages.pos-terminal';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('pos-terminal'),
        ];
    }

    public function getHeader(): null
    {
        return null; // Empty header to maximize space
    }

    public function mount(): void
    {
        redirect()->route('pos.terminal');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}

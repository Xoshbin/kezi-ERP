<?php

namespace App\Filament\Clusters\Accounting\Pages;

use App\Filament\Clusters\Accounting\Widgets\CashFlowWidget;
use App\Filament\Clusters\Accounting\Widgets\FinancialStatsOverview;
use App\Filament\Clusters\Accounting\Widgets\IncomeVsExpenseChart;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public static function getNavigationLabel(): string
    {
        return __('dashboard.title');
    }

    public function getTitle(): string
    {
        return __('dashboard.financial_dashboard');
    }

    public function getHeading(): string
    {
        return __('dashboard.financial_dashboard');
    }

    public function getSubheading(): ?string
    {
        $company = Filament::getTenant();
        $companyName = $company?->name ?? __('dashboard.no_company');

        return __('dashboard.welcome_message', [
            'company' => $companyName,
            'date' => now()->format('F j, Y'),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            FinancialStatsOverview::class,
            IncomeVsExpenseChart::class,
            CashFlowWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}

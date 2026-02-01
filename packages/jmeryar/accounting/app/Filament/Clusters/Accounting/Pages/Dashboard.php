<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Widgets\CashFlowWidget;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Widgets\FinancialStatsOverview;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Widgets\IncomeVsExpenseChart;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public static function getNavigationLabel(): string
    {
        return __('accounting::dashboard.title');
    }

    public function getTitle(): string
    {
        return __('accounting::dashboard.financial_dashboard');
    }

    public function getHeading(): string
    {
        return __('accounting::dashboard.financial_dashboard');
    }

    public function getSubheading(): ?string
    {
        $company = Filament::getTenant();
        $companyName = $company->name ?? __('accounting::dashboard.no_company');

        return __('accounting::dashboard.welcome_message', [
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

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('getting-started'),
        ];
    }
}

<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FinancialStatsOverview;
use App\Filament\Widgets\IncomeVsExpenseChart;
use App\Filament\Widgets\CashFlowWidget;
use App\Filament\Widgets\AccountWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';
    protected string $view = 'filament-panels::pages.dashboard';

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
        $user = auth()->user();
        $companyName = $user?->company?->name ?? __('dashboard.no_company');

        return __('dashboard.welcome_message', [
            'company' => $companyName,
            'date' => now()->format('F j, Y')
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

<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Widgets;

use App\Models\Company;
use App\Services\Reports\AgedPayableService;
use App\Services\Reports\AgedReceivableService;
use App\Services\Reports\BalanceSheetService;
use App\Services\Reports\ProfitAndLossStatementService;
use App\Support\NumberFormatter;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $company = Filament::getTenant();
        if (! $company instanceof Company) {
            return [];
        }

        // Get services
        $plService = app(ProfitAndLossStatementService::class);
        $bsService = app(BalanceSheetService::class);
        $arService = app(AgedReceivableService::class);
        $apService = app(AgedPayableService::class);

        // Date ranges
        $today = Carbon::now();
        $startOfMonth = $today->copy()->startOfMonth();
        $startOfYear = $today->copy()->startOfYear();

        try {
            // 1. Current Month Net Profit
            $plDto = $plService->generate($company, $startOfMonth, $today);
            $netProfit = $plDto->netIncome;
            $totalRevenue = $plDto->totalRevenue;

            // 2. Year-to-Date Net Profit
            $ytdPlDto = $plService->generate($company, $startOfYear, $today);
            $ytdNetProfit = $ytdPlDto->netIncome;

            // 3. Total Outstanding Receivables
            $arDto = $arService->generate($company, $today);
            $totalReceivables = $arDto->grandTotalDue;

            // 4. Total Outstanding Payables
            $apDto = $apService->generate($company, $today);
            $totalPayables = $apDto->grandTotalDue;

            // 5. Cash Balance (from Balance Sheet)
            $bsDto = $bsService->generate($company, $today);
            $cashBalance = $this->calculateCashBalance($bsDto);

            // 6. Gross Profit Margin (if we have revenue)
            $grossProfitMargin = $totalRevenue->isZero() ? 0 :
                ($totalRevenue->minus($plDto->totalExpenses))->getAmount()->toFloat() / $totalRevenue->getAmount()->toFloat() * 100;

        } catch (Exception) {
            // Fallback to zero values if services fail
            return [
                Stat::make(__('dashboard.financial.error'), __('dashboard.financial.data_unavailable'))
                    ->description(__('dashboard.financial.please_check_setup'))
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }

        return [
            // Current Month Net Profit
            Stat::make(__('dashboard.financial.current_month_profit'), NumberFormatter::formatMoneyTo($netProfit))
                ->description(__('dashboard.financial.profit_after_expenses'))
                ->descriptionIcon($netProfit->isPositive() ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netProfit->isPositive() ? 'success' : ($netProfit->isNegative() ? 'danger' : 'gray'))
                ->chart($this->getMonthlyProfitTrend($company, $plService)),

            // Year-to-Date Net Profit
            Stat::make(__('dashboard.financial.ytd_profit'), NumberFormatter::formatMoneyTo($ytdNetProfit))
                ->description(__('dashboard.financial.year_to_date_performance'))
                ->descriptionIcon($ytdNetProfit->isPositive() ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($ytdNetProfit->isPositive() ? 'success' : ($ytdNetProfit->isNegative() ? 'danger' : 'gray')),

            // Total Outstanding Receivables
            Stat::make(__('dashboard.financial.total_receivables'), NumberFormatter::formatMoneyTo($totalReceivables))
                ->description(__('dashboard.financial.outstanding_customer_invoices'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalReceivables->isZero() ? 'gray' : 'warning'),

            // Total Outstanding Payables
            Stat::make(__('dashboard.financial.total_payables'), NumberFormatter::formatMoneyTo($totalPayables))
                ->description(__('dashboard.financial.outstanding_vendor_bills'))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color($totalPayables->isZero() ? 'gray' : 'danger'),

            // Cash Balance
            Stat::make(__('dashboard.financial.cash_balance'), NumberFormatter::formatMoneyTo($cashBalance))
                ->description(__('dashboard.financial.total_cash_all_accounts'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($cashBalance->isPositive() ? 'success' : ($cashBalance->isNegative() ? 'danger' : 'gray')),

            // Gross Profit Margin
            Stat::make(__('dashboard.financial.gross_margin'), NumberFormatter::formatPercentage($grossProfitMargin, 1))
                ->description(__('dashboard.financial.profitability_ratio'))
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($grossProfitMargin > 20 ? 'success' : ($grossProfitMargin > 10 ? 'warning' : 'danger')),
        ];
    }

    private function calculateCashBalance(\App\DataTransferObjects\Reports\BalanceSheetDTO $balanceSheetDto): Money
    {
        $currency = $balanceSheetDto->assetLines->first()?->balance->getCurrency() ?? 'IQD';
        $cashBalance = Money::zero($currency);

        // Sum all bank and cash accounts from assets
        foreach ($balanceSheetDto->assetLines as $line) {
            // Assuming cash accounts have specific naming or we can identify them
            if (str_contains(strtolower($line->accountName), 'cash') ||
                str_contains(strtolower($line->accountName), 'bank')) {
                $cashBalance = $cashBalance->plus($line->balance);
            }
        }

        return $cashBalance;
    }

    /**
     * @return array<int, float>
     */
    private function getMonthlyProfitTrend(Company $company, ProfitAndLossStatementService $plService): array
    {
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();

            try {
                $plDto = $plService->generate($company, $startDate, $endDate);
                $data[] = $plDto->netIncome->getAmount()->toFloat();
            } catch (Exception) {
                $data[] = 0;
            }
        }

        return $data;
    }

    protected function getColumns(): int
    {
        return 3;
    }

    public static function canView(): bool
    {
        return true;
    }

    protected function getPollingInterval(): ?string
    {
        return '30s'; // Refresh every 30 seconds
    }
}

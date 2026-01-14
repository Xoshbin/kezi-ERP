<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Widgets;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Accounting\DataTransferObjects\Reports\BalanceSheetDTO;

class FinancialStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $company = Filament::getTenant();
        if (! $company instanceof Company) {
            return [];
        }

        // Get services
        $plService = app(\Modules\Accounting\Services\Reports\ProfitAndLossStatementService::class);
        $bsService = app(\Modules\Accounting\Services\Reports\BalanceSheetService::class);
        $arService = app(\Modules\Accounting\Services\Reports\AgedReceivableService::class);
        $apService = app(\Modules\Accounting\Services\Reports\AgedPayableService::class);

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
            $grossProfitMargin = $totalRevenue->isZero() ? 0 : ($totalRevenue->minus($plDto->totalExpenses))->getAmount()->toFloat() / $totalRevenue->getAmount()->toFloat() * 100;
        } catch (Exception) {
            // Fallback to zero values if services fail
            return [
                Stat::make(__('accounting::dashboard.financial.error'), __('accounting::dashboard.financial.data_unavailable'))
                    ->description(__('accounting::dashboard.financial.please_check_setup'))
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }

        return [
            // Current Month Net Profit
            Stat::make(__('accounting::dashboard.financial.current_month_profit'), \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($netProfit))
                ->description(__('accounting::dashboard.financial.profit_after_expenses'))
                ->descriptionIcon($netProfit->isPositive() ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netProfit->isPositive() ? 'success' : ($netProfit->isNegative() ? 'danger' : 'gray'))
                ->chart($this->getMonthlyProfitTrend($company, $plService)),

            // Year-to-Date Net Profit
            Stat::make(__('accounting::dashboard.financial.ytd_profit'), \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($ytdNetProfit))
                ->description(__('accounting::dashboard.financial.year_to_date_performance'))
                ->descriptionIcon($ytdNetProfit->isPositive() ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($ytdNetProfit->isPositive() ? 'success' : ($ytdNetProfit->isNegative() ? 'danger' : 'gray')),

            // Total Outstanding Receivables
            Stat::make(__('accounting::dashboard.financial.total_receivables'), \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($totalReceivables))
                ->description(__('accounting::dashboard.financial.outstanding_customer_invoices'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalReceivables->isZero() ? 'gray' : 'warning'),

            // Total Outstanding Payables
            Stat::make(__('accounting::dashboard.financial.total_payables'), \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($totalPayables))
                ->description(__('accounting::dashboard.financial.outstanding_vendor_bills'))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color($totalPayables->isZero() ? 'gray' : 'danger'),

            // Cash Balance
            Stat::make(__('accounting::dashboard.financial.cash_balance'), \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($cashBalance))
                ->description(__('accounting::dashboard.financial.total_cash_all_accounts'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($cashBalance->isPositive() ? 'success' : ($cashBalance->isNegative() ? 'danger' : 'gray')),

            // Gross Profit Margin
            Stat::make(__('accounting::dashboard.financial.gross_margin'), \Modules\Foundation\Support\NumberFormatter::formatPercentage($grossProfitMargin, 1))
                ->description(__('accounting::dashboard.financial.profitability_ratio'))
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($grossProfitMargin > 20 ? 'success' : ($grossProfitMargin > 10 ? 'warning' : 'danger')),
        ];
    }

    private function calculateCashBalance(BalanceSheetDTO $balanceSheetDto): Money
    {
        $currency = $balanceSheetDto->assetLines->first()?->balance->getCurrency() ?? 'IQD';
        $cashBalance = Money::zero($currency);

        // Sum all bank and cash accounts from assets
        foreach ($balanceSheetDto->assetLines as $line) {
            // Assuming cash accounts have specific naming or we can identify them
            if (
                str_contains(strtolower($line->accountName), 'cash') ||
                str_contains(strtolower($line->accountName), 'bank')
            ) {
                $cashBalance = $cashBalance->plus($line->balance);
            }
        }

        return $cashBalance;
    }

    /**
     * @return array<int, float>
     */
    private function getMonthlyProfitTrend(Company $company, \Modules\Accounting\Services\Reports\ProfitAndLossStatementService $plService): array
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

<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Widgets;

use Exception;
use Carbon\Carbon;
use App\Models\Company;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class CashFlowWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $company = Filament::getTenant();
        if (! $company instanceof Company) {
            return [];
        }

        $arService = app(\Modules\Accounting\Services\Reports\AgedReceivableService::class);
        $apService = app(\Modules\Accounting\Services\Reports\AgedPayableService::class);
        $today = Carbon::now();

        try {
            // Get aged receivables and payables
            $arDto = $arService->generate($company, $today);
            $apDto = $apService->generate($company, $today);

            // Use aging buckets for overdue amounts (90+ days is definitely overdue)
            $overdueReceivables = $arDto->totalBucket90_plus;
            $overduePayables = $apDto->totalBucket90_plus;

            // Use current + 1-30 day buckets as "due soon" (approximation for 7-day forecast)
            $receivablesDueSoon = $arDto->totalCurrent->plus($arDto->totalBucket1_30);
            $payablesDueSoon = $apDto->totalCurrent->plus($apDto->totalBucket1_30);

            // Use all buckets except 90+ for 30-day forecast
            $receivablesDue30Days = $arDto->totalCurrent
                ->plus($arDto->totalBucket1_30)
                ->plus($arDto->totalBucket31_60);
            $payablesDue30Days = $apDto->totalCurrent
                ->plus($apDto->totalBucket1_30)
                ->plus($apDto->totalBucket31_60);

            // Net cash flow forecast (receivables - payables)
            $netCashFlowSoon = $receivablesDueSoon->minus($payablesDueSoon);
            $netCashFlow30Days = $receivablesDue30Days->minus($payablesDue30Days);
        } catch (Exception) {
            return [
                Stat::make(__('dashboard.cash_flow.error'), __('dashboard.cash_flow.data_unavailable'))
                    ->description(__('dashboard.cash_flow.please_check_setup'))
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }

        return [
            // Overdue Receivables (90+ days)
            Stat::make(__('dashboard.cash_flow.overdue_receivables'), \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($overdueReceivables))
                ->description(__('dashboard.cash_flow.immediate_collection_needed'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueReceivables->isZero() ? 'success' : 'danger'),

            // Overdue Payables (90+ days)
            Stat::make(__('dashboard.cash_flow.overdue_payables'), \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($overduePayables))
                ->description(__('dashboard.cash_flow.immediate_payment_needed'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overduePayables->isZero() ? 'success' : 'warning'),

            // Near-term Cash Flow (Current + 1-30 days)
            Stat::make(__('dashboard.cash_flow.forecast_near_term'), \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($netCashFlowSoon))
                ->description(__('dashboard.cash_flow.net_cash_flow_soon'))
                ->descriptionIcon($netCashFlowSoon->isPositive() ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netCashFlowSoon->isPositive() ? 'success' : ($netCashFlowSoon->isNegative() ? 'danger' : 'gray')),

            // 30-Day Cash Flow Forecast (Current + 1-60 days)
            Stat::make(__('dashboard.cash_flow.forecast_30_days'), \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($netCashFlow30Days))
                ->description(__('dashboard.cash_flow.net_cash_flow_month'))
                ->descriptionIcon($netCashFlow30Days->isPositive() ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netCashFlow30Days->isPositive() ? 'success' : ($netCashFlow30Days->isNegative() ? 'danger' : 'gray')),
        ];
    }

    protected function getColumns(): int
    {
        return 2;
    }

    public static function canView(): bool
    {
        return true;
    }

    protected function getPollingInterval(): ?string
    {
        return '60s';
    }
}

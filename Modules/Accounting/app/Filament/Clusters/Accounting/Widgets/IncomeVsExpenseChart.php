<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Widgets;

use App\Services\Reports\ProfitAndLossStatementService;
use Carbon\Carbon;
use Exception;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class IncomeVsExpenseChart extends ChartWidget
{
    protected ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return __('dashboard.financial.income_vs_expense_chart');
    }

    protected function getData(): array
    {
        $company = Filament::getTenant();
        if (! $company instanceof \App\Models\Company) {
            return $this->getEmptyData();
        }

        $plService = app(\Modules\Accounting\Services\Reports\ProfitAndLossStatementService::class);

        $revenueData = [];
        $expenseData = [];
        $netIncomeData = [];
        $labels = [];

        try {
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $startDate = $date->copy()->startOfMonth();
                $endDate = $date->copy()->endOfMonth();

                $plDto = $plService->generate($company, $startDate, $endDate);

                // Convert to float for chart display
                $revenueData[] = $plDto->totalRevenue->getAmount()->toFloat();
                $expenseData[] = $plDto->totalExpenses->getAmount()->toFloat();
                $netIncomeData[] = $plDto->netIncome->getAmount()->toFloat();
                $labels[] = $date->format('M Y');
            }
        } catch (Exception $e) {
            return $this->getEmptyData();
        }

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.financial.total_revenue'),
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => __('dashboard.financial.total_expenses'),
                    'data' => $expenseData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => __('dashboard.financial.net_income'),
                    'data' => $netIncomeData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 3,
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat("'.app()->getLocale().'", {
                                    style: "currency",
                                    currency: "IQD",
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(context.parsed.y);
                            }
                            return label;
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => __('dashboard.financial.month'),
                    ],
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => __('dashboard.financial.amount'),
                    ],
                    'ticks' => [
                        'callback' => 'function(value, index, values) {
                            return new Intl.NumberFormat("'.app()->getLocale().'", {
                                style: "currency",
                                currency: "IQD",
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0,
                                notation: "compact"
                            }).format(value);
                        }',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getEmptyData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => __('dashboard.financial.no_data'),
                    'data' => [],
                    'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                    'borderColor' => 'rgb(156, 163, 175)',
                ],
            ],
            'labels' => [],
        ];
    }

    public static function canView(): bool
    {
        return true;
    }

    protected function getPollingInterval(): ?string
    {
        return '60s'; // Refresh every minute
    }
}

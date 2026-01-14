<?php

namespace Modules\ProjectManagement\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\ProjectManagement\Models\Project;

class BudgetVarianceWidget extends ChartWidget
{
    protected static ?string $heading = 'Budget Variance by Project';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $projects = Project::where('status', 'active')->take(10)->get();

        $datasets = [
            [
                'label' => 'Total Budget',
                'data' => [],
                'backgroundColor' => '#36A2EB',
            ],
            [
                'label' => 'Actual Cost',
                'data' => [],
                'backgroundColor' => '#FF6384',
            ],
        ];

        $labels = [];

        foreach ($projects as $project) {
            $labels[] = $project->name;
            $datasets[0]['data'][] = (float) $project->budget_amount;
            // We need actual cost. $project->getTotalActualCost() returns Money.
            // We need to convert to float/string for chart.
            // Assuming default currency for char logic.
            $actualCostMoney = $project->getTotalActualCost();
            $datasets[1]['data'][] = $actualCostMoney->getAmount()->toFloat();
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

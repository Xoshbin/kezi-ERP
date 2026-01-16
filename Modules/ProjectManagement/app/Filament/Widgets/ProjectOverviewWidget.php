<?php

namespace Modules\ProjectManagement\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\ProjectManagement\Models\Project;
use Modules\ProjectManagement\Models\Timesheet;

class ProjectOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeProjects = Project::where('status', 'active')->count();

        $totalBudget = Project::sum('budget_amount');
        // This is a simplified actual cost calculation.
        // In reality, we'd sum up analytic account balances or use the helper method Project::getTotalActualCost() and sum it up.
        // For efficiency in a widget, we might want to cache this or use a query.
        // Let's assume we fetch all projects and sum (not efficient for large data, but fine for now)
        // or just show total budget.

        // Let's stick to budget vs actual for ALL projects is heavy.
        // Let's just show Active Projects, Pending Timesheets, Overdue Tasks (maybe).

        $pendingTimesheets = Timesheet::where('status', 'submitted')->count();

        $overBudgetProjects = Project::all()->filter(function ($project) {
            return $project->getBudgetVariance() < 0; // Variance = Budget - Actual. If < 0, it's over budget.
        })->count();

        return [
            Stat::make(__('projectmanagement::project.widgets.overview.active_projects'), $activeProjects)
                ->description(__('projectmanagement::project.widgets.overview.currently_active'))
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success'),

            Stat::make(__('projectmanagement::project.widgets.overview.pending_timesheets'), $pendingTimesheets)
                ->description(__('projectmanagement::project.widgets.overview.waiting_approval'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make(__('projectmanagement::project.widgets.overview.over_budget'), $overBudgetProjects)
                ->description(__('projectmanagement::project.widgets.overview.exceeding_budget'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}

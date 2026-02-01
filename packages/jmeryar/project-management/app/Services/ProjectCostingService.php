<?php

namespace Jmeryar\ProjectManagement\Services;

use Brick\Money\Money;
use Jmeryar\ProjectManagement\Models\Project;

class ProjectCostingService
{
    /**
     * Get total project cost from analytic account.
     */
    public function getTotalProjectCost(Project $project): Money
    {
        return $project->getTotalActualCost();
    }

    /**
     * Get labor component of the cost.
     */
    public function getLaborCost(Project $project): Money
    {
        // In a real system, this would come from Journal Entries tagged with a specific "Labor" type or account
        // OR calculated from Timesheets * Cost Rate.
        // Let's rely on Analytic Account entries filtered by Account type "Labor" expenses if possible.
        // For this MVP, we'll simulate it or query analytic lines.

        // Simplified: Return zero or implement if specific accounts are known.
        return Money::zero($project->company->currency->code);
    }

    /**
     * Get material cost.
     */
    public function getMaterialCost(Project $project): Money
    {
        return Money::zero($project->company->currency->code);
    }

    /**
     * Get detailed cost report by period.
     */
    public function getCostByPeriod(Project $project, $startDate, $endDate): array
    {
        if (! $project->analyticAccount) {
            return [];
        }

        // Aggregate journal entries by period
        return [];
    }
}

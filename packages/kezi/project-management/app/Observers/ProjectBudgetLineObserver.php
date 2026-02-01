<?php

namespace Kezi\ProjectManagement\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Kezi\ProjectManagement\Models\ProjectBudgetLine;

class ProjectBudgetLineObserver implements ShouldHandleEventsAfterCommit
{
    public function created(ProjectBudgetLine $line): void
    {
        $this->updateBudgetTotal($line);
    }

    public function updated(ProjectBudgetLine $line): void
    {
        $this->updateBudgetTotal($line);
    }

    public function deleted(ProjectBudgetLine $line): void
    {
        $this->updateBudgetTotal($line);
    }

    protected function updateBudgetTotal(ProjectBudgetLine $line): void
    {
        $budget = $line->projectBudget;

        // Sum all lines
        $totalMinor = $budget->lines()->sum('budgeted_amount');

        // Update parent budget with Money object to ensure correct casting
        $totalMoney = \Brick\Money\Money::ofMinor($totalMinor, $budget->company->currency->code);
        $budget->updateQuietly(['total_budget' => $totalMoney]);
    }
}

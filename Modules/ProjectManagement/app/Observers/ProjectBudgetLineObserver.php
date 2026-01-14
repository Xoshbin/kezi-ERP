<?php

namespace Modules\ProjectManagement\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Modules\ProjectManagement\Models\ProjectBudgetLine;

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
        $total = $budget->lines()->sum('budgeted_amount');

        // Update parent budget
        // Using updateQuietly to avoid triggering parent observers if any (though currently none specific)
        $budget->updateQuietly(['budget_amount' => $total]);
    }
}

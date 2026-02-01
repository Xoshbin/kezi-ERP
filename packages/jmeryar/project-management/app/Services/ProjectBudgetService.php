<?php

namespace Jmeryar\ProjectManagement\Services;

use Jmeryar\ProjectManagement\Actions\CreateProjectBudgetAction;
use Jmeryar\ProjectManagement\DataTransferObjects\CreateProjectBudgetDTO;
use Jmeryar\ProjectManagement\Models\Project;
use Jmeryar\ProjectManagement\Models\ProjectBudget;

class ProjectBudgetService
{
    public function __construct(
        protected CreateProjectBudgetAction $createProjectBudgetAction,
    ) {}

    public function createBudget(CreateProjectBudgetDTO $dto): ProjectBudget
    {
        return $this->createProjectBudgetAction->execute($dto);
    }

    /**
     * Update actual amounts on budget lines from journal entries.
     */
    public function updateActuals(ProjectBudget $budget): void
    {
        $project = $budget->project;

        if (! $project->analyticAccount) {
            return;
        }

        $currency = $project->company->currency;
        $totalActual = \Brick\Money\Money::zero($currency->code);

        foreach ($budget->lines as $line) {
            // Calculate actuals for this account within the analytic account
            // Logic: Sum of (debit - credit) for JournalEntryLines that match:
            // 1. Analytic Account ID = Project's Analytic Account
            // 2. Account ID = Budget Line's Account
            // 3. Date range within Budget Start/End dates (optional, but good practice for fiscal years)

            $actualAmount = $project->analyticAccount->journalEntryLines()
                ->where('account_id', $line->account_id)
                ->whereHas('journalEntry', function ($query) use ($budget) {
                    $query->whereBetween('entry_date', [$budget->start_date, $budget->end_date]);
                })
                ->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));

            $actualMoney = \Brick\Money\Money::ofMinor($actualAmount, $currency->code);

            $line->update([
                'actual_amount' => $actualMoney,
            ]);
            $totalActual = $totalActual->plus($actualMoney);
        }

        $budget->update([
            'total_actual' => $totalActual,
        ]);
    }

    /**
     * Get budget variance report data.
     */
    public function getBudgetVarianceReport(Project $project): array
    {
        $activeBudget = $project->getActiveBudget();

        if (! $activeBudget) {
            return [];
        }

        return $activeBudget->lines->map(function ($line) {
            return [
                'account' => $line->account->name,
                'budgeted' => $line->getBudgetedMoney()->getAmount()->toFloat(),
                'actual' => $line->getActualMoney()->getAmount()->toFloat(),
                'variance' => $line->getVariance()->getAmount()->toFloat(),
                'is_over_budget' => $line->isOverBudget(),
            ];
        })->toArray();
    }

    public function getBudgetUtilizationPercentage(ProjectBudget $budget): float
    {
        return $budget->getUtilizationPercentage();
    }
}

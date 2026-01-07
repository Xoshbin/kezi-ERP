<?php

namespace Modules\ProjectManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\ProjectManagement\DataTransferObjects\CreateProjectBudgetDTO;
use Modules\ProjectManagement\Models\ProjectBudget;
use Modules\ProjectManagement\Models\ProjectBudgetLine;

class CreateProjectBudgetAction
{
    public function execute(CreateProjectBudgetDTO $dto): ProjectBudget
    {
        return DB::transaction(function () use ($dto) {
            $budget = ProjectBudget::create([
                'company_id' => $dto->company_id,
                'project_id' => $dto->project_id,
                'name' => $dto->name,
                'start_date' => $dto->start_date,
                'end_date' => $dto->end_date,
                'total_budget' => '0', // Will be updated
                'is_active' => true,
            ]);

            $company = \App\Models\Company::find($dto->company_id);
            $totalBudget = \Brick\Money\Money::zero($company->currency->code);

            foreach ($dto->lines as $lineDto) {
                // Convert integer/string minor units from DTO to Money object
                // This ensures BaseCurrencyMoneyCast treats it correctly (as Minor input converted to DB format)
                $amount = \Brick\Money\Money::ofMinor($lineDto->budgeted_amount, $company->currency->code);

                ProjectBudgetLine::create([
                    'company_id' => $dto->company_id,
                    'project_budget_id' => $budget->id,
                    'account_id' => $lineDto->account_id,
                    'description' => $lineDto->description,
                    'budgeted_amount' => $amount,
                ]);

                $totalBudget = $totalBudget->plus($amount);
            }

            $budget->update([
                'total_budget' => $totalBudget,
            ]);

            return $budget;
        });
    }
}

<?php

namespace Kezi\ProjectManagement\Actions;

use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Models\AnalyticAccount;
use Kezi\ProjectManagement\DataTransferObjects\CreateProjectDTO;
use Kezi\ProjectManagement\Models\Project;

class CreateProjectAction
{
    public function execute(CreateProjectDTO $dto): Project
    {
        return DB::transaction(function () use ($dto) {
            // Auto-create Analytic Account
            $analyticAccount = AnalyticAccount::create([
                'company_id' => $dto->company_id,
                'name' => $dto->name,
                'reference' => $dto->code,
                'is_active' => true,
            ]);

            return Project::create([
                'company_id' => $dto->company_id,
                'analytic_account_id' => $analyticAccount->id,
                'customer_id' => $dto->customer_id,
                'manager_id' => $dto->manager_id,
                'name' => $dto->name,
                'code' => $dto->code,
                'description' => $dto->description,
                'start_date' => $dto->start_date,
                'end_date' => $dto->end_date,
                'budget_amount' => $dto->budget_amount ?? '0',
                'billing_type' => $dto->billing_type,
                'is_billable' => $dto->is_billable,
            ]);
        });
    }
}

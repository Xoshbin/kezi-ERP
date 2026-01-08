<?php

namespace Modules\ProjectManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\ProjectManagement\DataTransferObjects\UpdateProjectDTO;
use Modules\ProjectManagement\Models\Project;

class UpdateProjectAction
{
    public function execute(Project $project, UpdateProjectDTO $dto): Project
    {
        return DB::transaction(function () use ($project, $dto) {
            $project->update([
                'name' => $dto->name,
                'code' => $dto->code,
                'description' => $dto->description,
                'manager_id' => $dto->manager_id,
                'customer_id' => $dto->customer_id,
                'status' => $dto->status,
                'start_date' => $dto->start_date,
                'end_date' => $dto->end_date,
                'budget_amount' => $dto->budget_amount ?? '0',
                'billing_type' => $dto->billing_type,
                'is_billable' => $dto->is_billable,
            ]);

            // Sync Analytic Account name/reference if changed
            if ($project->analyticAccount) {
                $project->analyticAccount->update([
                    'name' => $dto->name,
                    'reference' => $dto->code,
                ]);
            }

            return $project->refresh();
        });
    }
}

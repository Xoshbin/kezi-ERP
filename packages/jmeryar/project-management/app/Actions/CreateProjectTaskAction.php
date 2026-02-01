<?php

namespace Jmeryar\ProjectManagement\Actions;

use Illuminate\Support\Facades\DB;
use Jmeryar\ProjectManagement\DataTransferObjects\CreateProjectTaskDTO;
use Jmeryar\ProjectManagement\Models\ProjectTask;

class CreateProjectTaskAction
{
    public function execute(CreateProjectTaskDTO $dto): ProjectTask
    {
        return DB::transaction(function () use ($dto) {
            return ProjectTask::create([
                'company_id' => $dto->company_id,
                'project_id' => $dto->project_id,
                'parent_task_id' => $dto->parent_task_id,
                'assigned_to' => $dto->assigned_to,
                'name' => $dto->name,
                'description' => $dto->description,
                'start_date' => $dto->start_date,
                'due_date' => $dto->due_date,
                'estimated_hours' => $dto->estimated_hours ?? '0',
                'sequence' => $dto->sequence,
            ]);
        });
    }
}

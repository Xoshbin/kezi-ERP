<?php

namespace Modules\ProjectManagement\Services;

use Modules\ProjectManagement\Actions\CreateProjectAction;
use Modules\ProjectManagement\Actions\UpdateProjectAction;
use Modules\ProjectManagement\DataTransferObjects\CreateProjectDTO;
use Modules\ProjectManagement\DataTransferObjects\UpdateProjectDTO;
use Modules\ProjectManagement\Enums\ProjectStatus;
use Modules\ProjectManagement\Models\Project;

class ProjectService
{
    public function __construct(
        protected CreateProjectAction $createProjectAction,
        protected UpdateProjectAction $updateProjectAction,
    ) {}

    public function createProject(CreateProjectDTO $dto): Project
    {
        return $this->createProjectAction->execute($dto);
    }

    public function updateProject(Project $project, UpdateProjectDTO $dto): Project
    {
        return $this->updateProjectAction->execute($project, $dto);
    }

    public function activateProject(Project $project): void
    {
        $project->update(['status' => ProjectStatus::Active]);
    }

    public function completeProject(Project $project): void
    {
        $project->update(['status' => ProjectStatus::Completed]);
    }

    public function cancelProject(Project $project): void
    {
        $project->update(['status' => ProjectStatus::Cancelled]);
    }

    /**
     * Get project cost summary for dashboard/reporting.
     *
     * @return array{
     *     budget: float,
     *     actual: float,
     *     variance: float,
     *     utilization_percent: float
     * }
     */
    public function getProjectCostSummary(Project $project): array
    {
        $budget = $project->getTotalBudget();
        $actual = $project->getTotalActualCost();
        $variance = $project->getBudgetVariance();

        $budgetAmount = $budget->getAmount()->toFloat();
        $actualAmount = $actual->getAmount()->toFloat();

        $utilizationPercent = $budgetAmount > 0
            ? round(($actualAmount / $budgetAmount) * 100, 2)
            : 0;

        return [
            'budget' => $budgetAmount,
            'actual' => $actualAmount,
            'variance' => $variance->getAmount()->toFloat(),
            'utilization_percent' => $utilizationPercent,
        ];
    }
}

<?php

namespace Jmeryar\ProjectManagement\Services;

use Jmeryar\ProjectManagement\Actions\CreateProjectAction;
use Jmeryar\ProjectManagement\Actions\UpdateProjectAction;
use Jmeryar\ProjectManagement\DataTransferObjects\CreateProjectDTO;
use Jmeryar\ProjectManagement\DataTransferObjects\UpdateProjectDTO;
use Jmeryar\ProjectManagement\Enums\ProjectStatus;
use Jmeryar\ProjectManagement\Models\Project;

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
        $data = ['status' => ProjectStatus::Completed];

        if (! $project->end_date) {
            $data['end_date'] = now();
        }

        $project->update($data);
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

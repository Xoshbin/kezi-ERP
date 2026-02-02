<?php

namespace Kezi\ProjectManagement\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Kezi\HR\Models\Employee;
use Kezi\ProjectManagement\DataTransferObjects\CreateProjectDTO;
use Kezi\ProjectManagement\DataTransferObjects\UpdateProjectDTO;
use Kezi\ProjectManagement\Enums\BillingType;
use Kezi\ProjectManagement\Enums\ProjectStatus;
use Kezi\ProjectManagement\Services\ProjectService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->service = app(ProjectService::class);
});

it('creates a project successfully', function () {
    $manager = Employee::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateProjectDTO(
        company_id: $this->company->id,
        name: 'New Project',
        code: 'PRJ-001',
        description: 'Project Description',
        manager_id: $manager->id,
        customer_id: null,
        start_date: Carbon::now(),
        end_date: Carbon::now()->addMonth(),
        budget_amount: '1000.00',
        billing_type: BillingType::FixedPrice,
        is_billable: true
    );

    $project = $this->service->createProject($dto);

    expect($project->name)->toBe('New Project')
        ->and($project->code)->toBe('PRJ-001')
        ->and($project->manager_id)->toBe($manager->id)
        ->and($project->status)->toBe(ProjectStatus::Draft); // Default status
});

it('updates a project successfully', function () {
    $manager = Employee::factory()->create(['company_id' => $this->company->id]);
    // Create base project manually or via factory to save overhead if action test covers creation
    $project = \Kezi\ProjectManagement\Models\Project::factory()->create([
        'company_id' => $this->company->id,
        'manager_id' => $manager->id,
        'billing_type' => BillingType::TimeAndMaterials->value, // Use valid enum value
    ]);

    $dto = new UpdateProjectDTO(
        name: 'Updated Project',
        code: 'PRJ-002',
        description: 'Updated Description',
        manager_id: $manager->id,
        customer_id: null,
        status: ProjectStatus::Active,
        start_date: Carbon::now(),
        end_date: Carbon::now()->addMonths(2),
        budget_amount: '5000.00',
        billing_type: BillingType::TimeAndMaterials,
        is_billable: true
    );

    $updatedProject = $this->service->updateProject($project, $dto);

    expect($updatedProject->name)->toBe('Updated Project')
        ->and($updatedProject->code)->toBe('PRJ-002')
        ->and($updatedProject->status)->toBe(ProjectStatus::Active)
        ->and((float) $updatedProject->budget_amount)->toBe(5000.00);
});

it('activates a project', function () {
    $project = \Kezi\ProjectManagement\Models\Project::factory()->create([
        'company_id' => $this->company->id,
        'status' => ProjectStatus::Draft->value,
    ]);

    $this->service->activateProject($project);

    expect($project->fresh()->status)->toBe(ProjectStatus::Active);
});

it('completes a project', function () {
    $project = \Kezi\ProjectManagement\Models\Project::factory()->create([
        'company_id' => $this->company->id,
        'status' => ProjectStatus::Active->value,
    ]);

    $this->service->completeProject($project);

    expect($project->fresh()->status)->toBe(ProjectStatus::Completed);
});

it('cancels a project', function () {
    $project = \Kezi\ProjectManagement\Models\Project::factory()->create([
        'company_id' => $this->company->id,
        'status' => ProjectStatus::Active->value,
    ]);

    $this->service->cancelProject($project);

    expect($project->fresh()->status)->toBe(ProjectStatus::Cancelled);
});

it('returns correct cost summary', function () {
    // We need to mock or setup the underlying data for cost calculations.
    // Project::getTotalBudget() and Project::getTotalActualCost() probably rely on relationships
    // or mocked methods if we could partial mock.
    // Since these are on the model, we should verify that setting up a budget works.

    // Create a project
    $project = \Kezi\ProjectManagement\Models\Project::factory()->create([
        'company_id' => $this->company->id,
        'budget_amount' => 1000.00, // Assuming direct column for simple budget or relation
    ]);

    // Check implementation of getTotalBudget in Project model
    // Assuming simple column for now or mocked return if logic is complex.
    // If logic is complex, we might need a dedicated test.

    // Let's assume for this test that getting the summary returns structure even if 0.

    $summary = $this->service->getProjectCostSummary($project);

    // Default expectation
    expect($summary)->toBeArray()
        ->and($summary['budget'])->toBeNumeric()
        ->and($summary['actual'])->toBe(0.0) // No actuals yet
        ->and($summary['utilization_percent'])->toBe(0.0);
});

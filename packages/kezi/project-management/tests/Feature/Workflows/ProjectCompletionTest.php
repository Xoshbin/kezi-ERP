<?php

use Kezi\ProjectManagement\Enums\ProjectStatus;
use Kezi\ProjectManagement\Models\Project;
use Kezi\ProjectManagement\Services\ProjectService;

uses(Tests\Traits\WithConfiguredCompany::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->project = Project::factory()->create([
        'company_id' => $this->company->id,
        'status' => ProjectStatus::Active,
        'start_date' => now()->subDays(10),
        'end_date' => null,
    ]);
});

it('can transition a project from active to completed', function () {
    $service = app(ProjectService::class);

    $service->completeProject($this->project);

    expect($this->project->refresh())
        ->status->toBe(ProjectStatus::Completed);
});

it('automatically sets end_date when project is completed if not set', function () {
    $service = app(ProjectService::class);

    $service->completeProject($this->project);

    expect($this->project->refresh())
        ->status->toBe(ProjectStatus::Completed)
        ->end_date->not->toBeNull()
        ->end_date->toDateString()->toBe(now()->toDateString());
});

it('does not overwrite existing end_date when project is completed', function () {
    $existingEndDate = now()->subDay()->toDateString();
    $this->project->update(['end_date' => $existingEndDate]);

    $service = app(ProjectService::class);

    $service->completeProject($this->project);

    expect($this->project->refresh())
        ->status->toBe(ProjectStatus::Completed)
        ->end_date->toDateString()->toBe($existingEndDate);
});

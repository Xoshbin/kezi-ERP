<?php

namespace Kezi\ProjectManagement\Tests\Feature\Services;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\ProjectManagement\Services\ProjectCostingService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->service = app(ProjectCostingService::class);
});

it('gets total project cost', function () {
    $project = \Kezi\ProjectManagement\Models\Project::factory()->create([
        'company_id' => $this->company->id,
    ]);

    // Currently assumes 0 if no data
    $cost = $this->service->getTotalProjectCost($project);

    expect($cost)->toBeInstanceOf(Money::class)
        ->and($cost->getAmount()->toFloat())->toBe(0.0);
});

it('gets labor cost returns zero for now', function () {
    $project = \Kezi\ProjectManagement\Models\Project::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $cost = $this->service->getLaborCost($project);

    expect($cost)->toBeInstanceOf(Money::class)
        ->and($cost->getAmount()->toFloat())->toBe(0.0);
});

it('gets material cost returns zero for now', function () {
    $project = \Kezi\ProjectManagement\Models\Project::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $cost = $this->service->getMaterialCost($project);

    expect($cost)->toBeInstanceOf(Money::class)
        ->and($cost->getAmount()->toFloat())->toBe(0.0);
});

it('gets cost by period returns empty array', function () {
    $project = \Kezi\ProjectManagement\Models\Project::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $report = $this->service->getCostByPeriod($project, now()->subMonth(), now());

    expect($report)->toBeArray()
        ->and($report)->toBeEmpty();
});

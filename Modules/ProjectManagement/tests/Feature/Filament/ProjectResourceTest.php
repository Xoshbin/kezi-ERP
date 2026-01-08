<?php

namespace Modules\ProjectManagement\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Partner;
use Modules\HR\Models\Employee;
use Modules\ProjectManagement\Enums\BillingType;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Pages\CreateProject;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Pages\EditProject;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\ProjectResource;
use Modules\ProjectManagement\Models\Project;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setCurrentPanel('jmeryar');
    Filament::setTenant($this->company);
    $this->actingAs($this->user);

    // Create employee record for manager relationship
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $this->user->update(['current_company_id' => $this->company->id]);
    $this->user->refresh();
    $this->actingAs($this->user);
});

it('can render the list page', function () {
    $this->get(ProjectResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(ProjectResource::getUrl('create'))->assertSuccessful();
});

it('can create a project', function () {
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(CreateProject::class)
        ->fillForm([
            'name' => 'Test Project',
            'code' => 'PRJ-001',
            'customer_id' => $customer->id,
            'manager_id' => $this->employee->id,
            'status' => 'draft',
            'budget_amount' => '1000',
            'billing_type' => BillingType::TimeAndMaterials->value,
            'is_billable' => true,
            'company_id' => $this->company->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('projects', [
        'name' => 'Test Project',
        'code' => 'PRJ-001',
        'customer_id' => $customer->id,
        'company_id' => $this->company->id,
        'billing_type' => BillingType::TimeAndMaterials->value,
        'is_billable' => true,
    ]);
});

it('can render the edit page', function () {
    $project = Project::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(ProjectResource::getUrl('edit', ['record' => $project]))
        ->assertSuccessful();
});

it('can edit a project', function () {
    $project = Project::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Original Name',
    ]);

    livewire(EditProject::class, [
        'record' => $project->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($project->refresh()->name)->toBe('Updated Name');
});

it('can activate a project via action', function () {
    $project = Project::factory()->create([
        'company_id' => $this->company->id,
        'status' => 'draft',
    ]);

    livewire(EditProject::class, [
        'record' => $project->getRouteKey(),
    ])
        ->callAction('activate')
        ->assertHasNoErrors();

    expect($project->refresh()->status->value)->toBe('active');
});

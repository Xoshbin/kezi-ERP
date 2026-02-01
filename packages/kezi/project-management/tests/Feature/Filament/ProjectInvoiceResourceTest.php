<?php

namespace Kezi\ProjectManagement\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Pages\CreateProjectInvoice;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Pages\EditProjectInvoice;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\ProjectInvoiceResource;
use Kezi\ProjectManagement\Models\Project;
use Kezi\ProjectManagement\Models\ProjectInvoice;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setCurrentPanel('kezi');
    Filament::setTenant($this->company);
    
    $this->user->update(['current_company_id' => $this->company->id]);
    $this->user->refresh();
    $this->actingAs($this->user);
});

it('can render the list page', function () {
    $this->get(ProjectInvoiceResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(ProjectInvoiceResource::getUrl('create'))->assertSuccessful();
});

it('can create a project invoice', function () {
    $project = Project::factory()->create(['company_id' => $this->company->id]);

    livewire(CreateProjectInvoice::class)
        ->fillForm([
            'project_id' => $project->id,
            'invoice_date' => now()->format('Y-m-d'),
            'period_start' => now()->startOfMonth()->format('Y-m-d'),
            'period_end' => now()->endOfMonth()->format('Y-m-d'),
            'include_labor' => true,
            'include_expenses' => true,
            'company_id' => $this->company->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('project_invoices', [
        'project_id' => $project->id,
        'company_id' => $this->company->id,
        'status' => 'draft',
    ]);
});

it('can render the edit page', function () {
    $projectInvoice = ProjectInvoice::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(ProjectInvoiceResource::getUrl('edit', ['record' => $projectInvoice]))
        ->assertSuccessful();
});

it('can edit a project invoice', function () {
    $projectInvoice = ProjectInvoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => 'draft',
    ]);

    livewire(EditProjectInvoice::class, [
        'record' => $projectInvoice->getRouteKey(),
    ])
        ->fillForm([
            'status' => 'draft', // Just a placeholder edit
        ])
        ->call('save')
        ->assertHasNoFormErrors();
});

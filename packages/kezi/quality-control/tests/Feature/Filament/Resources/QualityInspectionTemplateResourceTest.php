<?php

/** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */

namespace Kezi\QualityControl\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource\Pages\CreateQualityInspectionTemplate;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource\Pages\EditQualityInspectionTemplate;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource\Pages\ListQualityInspectionTemplates;
use Kezi\QualityControl\Models\QualityInspectionTemplate;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
    $this->actingAs($this->user);
});

describe('QualityInspectionTemplateResource', function () {
    it('can render list page', function () {
        $this->get(QualityInspectionTemplateResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can list quality inspection templates', function () {
        $record = QualityInspectionTemplate::factory()
            ->for($this->company)
            ->create(['name' => 'TEMPLATE-TEST']);

        livewire(ListQualityInspectionTemplates::class)
            ->assertCanRenderTableColumn('name')
            ->assertSee('TEMPLATE-TEST');
    });

    it('can create quality inspection template', function () {
        livewire(CreateQualityInspectionTemplate::class)
            ->fillForm([
                'name' => 'New Template',
                'description' => 'Template description',
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('quality_inspection_templates', [
            'name' => 'New Template',
            'company_id' => $this->company->id,
        ]);
    });

    it('can edit quality inspection template', function () {
        $record = QualityInspectionTemplate::factory()
            ->for($this->company)
            ->create(['name' => 'Old Template Name']);

        livewire(EditQualityInspectionTemplate::class, [
            'record' => $record->getRouteKey(),
        ])
            ->fillForm([
                'name' => 'New Template Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($record->refresh())->name->toBe('New Template Name');
    });

    it('can delete quality inspection template', function () {
        $record = QualityInspectionTemplate::factory()
            ->for($this->company)
            ->create();

        livewire(EditQualityInspectionTemplate::class, [
            'record' => $record->getRouteKey(),
        ])
            ->callAction(\Filament\Actions\DeleteAction::class);

        $this->assertModelMissing($record);
    });

    it('can validate required fields', function () {
        livewire(CreateQualityInspectionTemplate::class)
            ->fillForm([
                'name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
            ]);
    });
});

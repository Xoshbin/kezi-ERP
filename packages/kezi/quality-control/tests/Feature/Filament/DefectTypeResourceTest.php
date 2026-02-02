<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages\CreateDefectType;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages\EditDefectType;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages\ListDefectTypes;
use Kezi\QualityControl\Models\DefectType;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can render defect type list page', function () {
    livewire(ListDefectTypes::class)
        ->assertSuccessful();
});

it('can create a defect type', function () {
    livewire(CreateDefectType::class)
        ->fillForm([
            'code' => 'SCRATCH',
            'name' => 'Surface Scratch',
            'description' => 'Minor scratches on surface',
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $defectType = DefectType::where('code', 'SCRATCH')->first();

    expect($defectType)
        ->not->toBeNull()
        ->code->toBe('SCRATCH')
        ->name->toBe('Surface Scratch')
        ->company_id->toBe($this->company->id);
});

it('validates required fields when creating defect type', function () {
    livewire(CreateDefectType::class)
        ->fillForm([
            'code' => '',
            'name' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['code', 'name']);
});

it('can edit a defect type', function () {
    $defectType = DefectType::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'DENT',
        'name' => 'Dent',
    ]);

    livewire(EditDefectType::class, [
        'record' => $defectType->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'Deep Dent',
            'description' => 'Severe denting',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($defectType->fresh())
        ->name->toBe('Deep Dent')
        ->description->toBe('Severe denting');
});

it('can list defect types in table', function () {
    $defectTypes = DefectType::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListDefectTypes::class)
        ->assertCanSeeTableRecords($defectTypes);
});

it('can filter defect types by active status', function () {
    $activeDefect = DefectType::factory()->create([
        'company_id' => $this->company->id,
        'active' => true,
    ]);

    $inactiveDefect = DefectType::factory()->create([
        'company_id' => $this->company->id,
        'active' => false,
    ]);

    livewire(ListDefectTypes::class)
        ->filterTable('active', true)
        ->assertCanSeeTableRecords([$activeDefect])
        ->assertCanNotSeeTableRecords([$inactiveDefect]);
});

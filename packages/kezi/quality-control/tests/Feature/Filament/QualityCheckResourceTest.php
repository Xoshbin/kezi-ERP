<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Product\Models\Product;
use Kezi\QualityControl\Enums\QualityCheckStatus;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource\Pages\ListQualityChecks;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource\Pages\ViewQualityCheck;
use Kezi\QualityControl\Models\QualityCheck;
use Kezi\QualityControl\Models\QualityInspectionTemplate;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can render quality check list page', function () {
    livewire(ListQualityChecks::class)
        ->assertSuccessful();
});

it('can view a quality check', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $template = QualityInspectionTemplate::factory()->create(['company_id' => $this->company->id]);

    $check = QualityCheck::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'status' => QualityCheckStatus::Draft,
    ]);

    livewire(ViewQualityCheck::class, [
        'record' => $check->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSee($check->number)
        ->assertSee($product->name);
});

it('can list quality checks in table', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $template = QualityInspectionTemplate::factory()->create(['company_id' => $this->company->id]);

    $checks = QualityCheck::factory()->count(3)->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
    ]);

    livewire(ListQualityChecks::class)
        ->assertCanSeeTableRecords($checks);
});

it('can filter quality checks by status', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $template = QualityInspectionTemplate::factory()->create(['company_id' => $this->company->id]);

    $draftCheck = QualityCheck::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'status' => QualityCheckStatus::Draft,
    ]);

    $passedCheck = QualityCheck::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'status' => QualityCheckStatus::Passed,
    ]);

    livewire(ListQualityChecks::class)
        ->filterTable('status', QualityCheckStatus::Draft->value)
        ->assertCanSeeTableRecords([$draftCheck])
        ->assertCanNotSeeTableRecords([$passedCheck]);
});

it('can search quality checks by number', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $template = QualityInspectionTemplate::factory()->create(['company_id' => $this->company->id]);

    $check1 = QualityCheck::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'number' => 'QC-001234',
    ]);

    $check2 = QualityCheck::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'number' => 'QC-005678',
    ]);

    livewire(ListQualityChecks::class)
        ->searchTable('QC-001234')
        ->assertCanSeeTableRecords([$check1])
        ->assertCanNotSeeTableRecords([$check2]);
});

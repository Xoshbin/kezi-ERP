<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Product\Models\Product;
use Kezi\QualityControl\Enums\QualityCheckStatus;
use Kezi\QualityControl\Enums\QualityCheckType;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource\Pages\ViewQualityCheck;
use Kezi\QualityControl\Models\QualityCheck;
use Kezi\QualityControl\Models\QualityInspectionParameter;
use Kezi\QualityControl\Models\QualityInspectionTemplate;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can record results for a quality check', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $template = QualityInspectionTemplate::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $param1 = QualityInspectionParameter::factory()->create([
        'template_id' => $template->id,
        'name' => 'Visual Check',
        'check_type' => QualityCheckType::PassFail,
    ]);

    $param2 = QualityInspectionParameter::factory()->create([
        'template_id' => $template->id,
        'name' => 'Measurement',
        'check_type' => QualityCheckType::Measure,
        'min_value' => 10,
        'max_value' => 20,
    ]);

    $check = QualityCheck::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'status' => QualityCheckStatus::Draft,
    ]);

    $check->lines()->create(['parameter_id' => $param1->id]);
    $check->lines()->create(['parameter_id' => $param2->id]);

    livewire(ViewQualityCheck::class, [
        'record' => $check->getRouteKey(),
    ])
        ->assertActionVisible('record_results')
        ->callAction('record_results', data: [
            'results' => [
                $param1->id => ['result_pass_fail' => true],
                $param2->id => ['result_numeric' => 15.5],
            ],
            'notes' => 'Everything looks good.',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified(__('qualitycontrol::check.record_results_success'));

    $check->refresh();
    expect($check->status)->toBe(QualityCheckStatus::Passed);
    expect($check->inspected_by_user_id)->toBe(auth()->id());
    expect($check->notes)->toBe('Everything looks good.');

    expect($check->lines->where('parameter_id', $param1->id)->first()->result_pass_fail)->toBeTrue();
    expect((float) $check->lines->where('parameter_id', $param2->id)->first()->result_numeric)->toBe(15.5);
});

it('hides record results action when check is not in draft', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $template = QualityInspectionTemplate::factory()->create(['company_id' => $this->company->id]);

    $check = QualityCheck::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'status' => QualityCheckStatus::Passed,
    ]);

    livewire(ViewQualityCheck::class, [
        'record' => $check->getRouteKey(),
    ])
        ->assertActionHidden('record_results');
});

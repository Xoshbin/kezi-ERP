<?php

use App\Models\Company;
use App\Models\User;
use Jmeryar\Product\Models\Product;
use Jmeryar\QualityControl\Actions\RecordQualityCheckResultAction;
use Jmeryar\QualityControl\DataTransferObjects\RecordQualityCheckResultDTO;
use Jmeryar\QualityControl\Enums\QualityCheckStatus;
use Jmeryar\QualityControl\Enums\QualityCheckType;
use Jmeryar\QualityControl\Models\QualityCheck;
use Jmeryar\QualityControl\Models\QualityInspectionParameter;
use Jmeryar\QualityControl\Models\QualityInspectionTemplate;

it('records pass/fail results and determines overall status', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);

    // Create template with parameters
    $template = QualityInspectionTemplate::factory()->create([
        'company_id' => $company->id,
    ]);

    $param1 = QualityInspectionParameter::factory()->create([
        'template_id' => $template->id,
        'name' => 'Visual Inspection',
        'check_type' => QualityCheckType::PassFail,
    ]);

    $param2 = QualityInspectionParameter::factory()->create([
        'template_id' => $template->id,
        'name' => 'Packaging Check',
        'check_type' => QualityCheckType::PassFail,
    ]);

    // Create quality check with lines
    $check = QualityCheck::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'status' => QualityCheckStatus::Draft,
    ]);

    $check->lines()->create(['parameter_id' => $param1->id]);
    $check->lines()->create(['parameter_id' => $param2->id]);

    // Record results - all pass
    $dto = new RecordQualityCheckResultDTO(
        qualityCheckId: $check->id,
        inspectedByUserId: $user->id,
        lineResults: [
            ['parameter_id' => $param1->id, 'result_pass_fail' => true],
            ['parameter_id' => $param2->id, 'result_pass_fail' => true],
        ],
    );

    $action = app(RecordQualityCheckResultAction::class);
    $result = $action->execute($dto);

    expect($result)
        ->status->toBe(QualityCheckStatus::Passed)
        ->inspected_by_user_id->toBe($user->id)
        ->inspected_at->not->toBeNull();

    expect($result->lines)->toHaveCount(2);
    expect($result->lines->every(fn ($line) => $line->result_pass_fail === true))->toBeTrue();
});

it('marks check as failed when any parameter fails', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);

    $template = QualityInspectionTemplate::factory()->create([
        'company_id' => $company->id,
    ]);

    $param1 = QualityInspectionParameter::factory()->create([
        'template_id' => $template->id,
        'check_type' => QualityCheckType::PassFail,
    ]);

    $param2 = QualityInspectionParameter::factory()->create([
        'template_id' => $template->id,
        'check_type' => QualityCheckType::PassFail,
    ]);

    $check = QualityCheck::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'status' => QualityCheckStatus::Draft,
    ]);

    $check->lines()->create(['parameter_id' => $param1->id]);
    $check->lines()->create(['parameter_id' => $param2->id]);

    // One passes, one fails
    $dto = new RecordQualityCheckResultDTO(
        qualityCheckId: $check->id,
        inspectedByUserId: $user->id,
        lineResults: [
            ['parameter_id' => $param1->id, 'result_pass_fail' => true],
            ['parameter_id' => $param2->id, 'result_pass_fail' => false],
        ],
    );

    $action = app(RecordQualityCheckResultAction::class);
    $result = $action->execute($dto);

    expect($result->status)->toBe(QualityCheckStatus::Failed);
});

it('calculates tolerance for measure type parameters', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);

    $template = QualityInspectionTemplate::factory()->create([
        'company_id' => $company->id,
    ]);

    $param = QualityInspectionParameter::factory()->create([
        'template_id' => $template->id,
        'name' => 'Weight',
        'check_type' => QualityCheckType::Measure,
        'min_value' => 100,
        'max_value' => 110,
        'unit_of_measure' => 'g',
    ]);

    $check = QualityCheck::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'status' => QualityCheckStatus::Draft,
    ]);

    $check->lines()->create(['parameter_id' => $param->id]);

    // Within tolerance
    $dto = new RecordQualityCheckResultDTO(
        qualityCheckId: $check->id,
        inspectedByUserId: $user->id,
        lineResults: [
            ['parameter_id' => $param->id, 'result_numeric' => 105.5],
        ],
    );

    $action = app(RecordQualityCheckResultAction::class);
    $result = $action->execute($dto);

    expect($result->lines->first())
        ->result_numeric->toEqual(105.5)
        ->is_within_tolerance->toBeTrue();

    expect($result->status)->toBe(QualityCheckStatus::Passed);
});

it('marks as failed when measurement is out of tolerance', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);

    $template = QualityInspectionTemplate::factory()->create([
        'company_id' => $company->id,
    ]);

    $param = QualityInspectionParameter::factory()->create([
        'template_id' => $template->id,
        'check_type' => QualityCheckType::Measure,
        'min_value' => 100,
        'max_value' => 110,
    ]);

    $check = QualityCheck::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'status' => QualityCheckStatus::Draft,
    ]);

    $check->lines()->create(['parameter_id' => $param->id]);

    // Out of tolerance (too high)
    $dto = new RecordQualityCheckResultDTO(
        qualityCheckId: $check->id,
        inspectedByUserId: $user->id,
        lineResults: [
            ['parameter_id' => $param->id, 'result_numeric' => 115],
        ],
    );

    $action = app(RecordQualityCheckResultAction::class);
    $result = $action->execute($dto);

    expect($result->lines->first())
        ->is_within_tolerance->toBeFalse();

    expect($result->status)->toBe(QualityCheckStatus::Failed);
});

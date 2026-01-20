<?php

namespace Modules\QualityControl\Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Models\Lot;
use Modules\Product\Models\Product;
use Modules\QualityControl\Enums\QualityCheckStatus;
use Modules\QualityControl\Models\QualityCheck;
use Modules\QualityControl\Models\QualityInspectionTemplate;

uses(RefreshDatabase::class);

test('failing a quality check deactivates the associated lot', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $product = Product::factory()->create(['company_id' => $company->id]);
    $lot = Lot::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'active' => true,
    ]);

    $check = QualityCheck::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'lot_id' => $lot->id,
        'status' => QualityCheckStatus::InProgress,
    ]);

    // Create a failed line result
    $template = QualityInspectionTemplate::factory()->create(['company_id' => $company->id]);
    $parameter = $template->parameters()->create([
        'name' => 'Visual Inspection',
        'check_type' => \Modules\QualityControl\Enums\QualityCheckType::PassFail,
        'sequence' => 1,
    ]);

    // We need a parameter for the line
    $check->lines()->create([
        'quality_check_id' => $check->id,
        'parameter_id' => $parameter->id,
        'result_pass_fail' => false,
    ]);

    // Mocking the DTO execution effectively by updating the check status directly
    // This isolates the test to "When Check Fails -> Lot Deactivated" logic
    // rather than "Action correctly fails check".
    // Let's try updating the status directly first as it's the trigger.

    $check->update(['status' => QualityCheckStatus::Failed, 'notes' => 'Failed by test']);

    $lot->refresh();

    expect($lot->active)->toBeFalse()
        ->and($lot->rejection_reason)->toBe('Failed by test');
});

test('passing a quality check keeps the lot active', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->create(['company_id' => $company->id]);
    $lot = Lot::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'active' => true,
    ]);

    $check = QualityCheck::factory()->create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'lot_id' => $lot->id,
        'status' => QualityCheckStatus::InProgress,
    ]);

    $check->update(['status' => QualityCheckStatus::Passed]);

    $lot->refresh();

    expect($lot->active)->toBeTrue();
});

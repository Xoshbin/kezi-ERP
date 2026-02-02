<?php

use App\Models\Company;
use App\Models\User;
use Kezi\Product\Models\Product;
use Kezi\QualityControl\Enums\QualityTriggerFrequency;
use Kezi\QualityControl\Enums\QualityTriggerOperation;
use Kezi\QualityControl\Models\QualityControlPoint;
use Kezi\QualityControl\Models\QualityInspectionTemplate;
use Kezi\QualityControl\Services\QualityControlPointService;

it('finds control points for specific operation and product', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);
    $template = QualityInspectionTemplate::factory()->create(['company_id' => $company->id]);

    // Control point for this specific product
    $qcp1 = QualityControlPoint::factory()->create([
        'company_id' => $company->id,
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'trigger_frequency' => QualityTriggerFrequency::PerOperation,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'active' => true,
    ]);

    // Control point for all products (null product_id)
    $qcp2 = QualityControlPoint::factory()->create([
        'company_id' => $company->id,
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'trigger_frequency' => QualityTriggerFrequency::PerOperation,
        'product_id' => null, // Applies to all
        'inspection_template_id' => $template->id,
        'active' => true,
    ]);

    // Different operation - should not match
    QualityControlPoint::factory()->create([
        'company_id' => $company->id,
        'trigger_operation' => QualityTriggerOperation::ManufacturingOutput,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'active' => true,
    ]);

    $service = app(QualityControlPointService::class);
    $triggered = $service->findTriggeredControlPoints(
        QualityTriggerOperation::GoodsReceipt,
        $product,
        10
    );

    expect($triggered)->toHaveCount(2);
    expect($triggered->pluck('id')->toArray())->toContain($qcp1->id, $qcp2->id);
});

it('respects quantity threshold for per-quantity triggers', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);
    $template = QualityInspectionTemplate::factory()->create(['company_id' => $company->id]);

    // Requires minimum 100 units
    $qcp = QualityControlPoint::factory()->create([
        'company_id' => $company->id,
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'trigger_frequency' => QualityTriggerFrequency::PerQuantity,
        'product_id' => $product->id,
        'quantity_threshold' => 100,
        'inspection_template_id' => $template->id,
        'active' => true,
    ]);

    $service = app(QualityControlPointService::class);

    // Below threshold - should not trigger
    $triggered1 = $service->findTriggeredControlPoints(
        QualityTriggerOperation::GoodsReceipt,
        $product,
        50
    );

    expect($triggered1)->toHaveCount(0);

    // At threshold - should trigger
    $triggered2 = $service->findTriggeredControlPoints(
        QualityTriggerOperation::GoodsReceipt,
        $product,
        100
    );

    expect($triggered2)->toHaveCount(1);

    // Above threshold - should trigger
    $triggered3 = $service->findTriggeredControlPoints(
        QualityTriggerOperation::GoodsReceipt,
        $product,
        150
    );

    expect($triggered3)->toHaveCount(1);
});

it('identifies blocking control points', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);
    $template = QualityInspectionTemplate::factory()->create(['company_id' => $company->id]);

    QualityControlPoint::factory()->create([
        'company_id' => $company->id,
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'is_blocking' => true,
        'active' => true,
    ]);

    $service = app(QualityControlPointService::class);

    $hasBlocking = $service->hasBlockingControlPoints(
        QualityTriggerOperation::GoodsReceipt,
        $product
    );

    expect($hasBlocking)->toBeTrue();
});

it('only returns active control points', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $product = Product::factory()->create(['company_id' => $company->id]);
    $template = QualityInspectionTemplate::factory()->create(['company_id' => $company->id]);

    QualityControlPoint::factory()->create([
        'company_id' => $company->id,
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'active' => false, // Inactive
    ]);

    $service = app(QualityControlPointService::class);

    $triggered = $service->findTriggeredControlPoints(
        QualityTriggerOperation::GoodsReceipt,
        $product
    );

    expect($triggered)->toHaveCount(0);
});

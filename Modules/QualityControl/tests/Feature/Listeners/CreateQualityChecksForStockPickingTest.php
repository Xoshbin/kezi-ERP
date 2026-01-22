<?php

namespace Modules\QualityControl\Tests\Feature\Listeners;

use App\Models\Company;
use App\Models\User;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Enums\Inventory\StockPickingType;
use Modules\Inventory\Enums\Inventory\TrackingType;
use Modules\Inventory\Events\StockPickingValidated;
use Modules\Inventory\Models\Lot;
use Modules\Inventory\Models\SerialNumber;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Inventory\Models\StockPicking;
use Modules\Product\Models\Product;
use Modules\QualityControl\Enums\QualityCheckStatus;
use Modules\QualityControl\Enums\QualityTriggerFrequency;
use Modules\QualityControl\Enums\QualityTriggerOperation;
use Modules\QualityControl\Models\QualityCheck;
use Modules\QualityControl\Models\QualityControlPoint;
use Modules\QualityControl\Models\QualityInspectionTemplate;

/**
 * @param  array<string, array<string, mixed>>  $overrides
 * @return array{picking: StockPicking, stockMove: StockMove, productLine: StockMoveProductLine, product: Product}
 */
function createGoodsReceiptWithProductLine(
    Company $company,
    Product $product,
    float $quantity = 10.0,
    array $overrides = []
): array {
    /** @var StockPicking $picking */
    $picking = StockPicking::factory()->for($company)->create([
        'type' => StockPickingType::Receipt,
        'state' => StockPickingState::Done,
        ...$overrides['picking'] ?? [],
    ]);

    /** @var StockMove $stockMove */
    $stockMove = StockMove::factory()->for($company)->create([
        'picking_id' => $picking->id,
        'move_type' => StockMoveType::Incoming,
        'status' => StockMoveStatus::Done,
        ...$overrides['stockMove'] ?? [],
    ]);

    /** @var StockMoveProductLine $productLine */
    $productLine = StockMoveProductLine::factory()->for($company)->create([
        'stock_move_id' => $stockMove->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'from_location_id' => StockLocation::factory()->for($company)->create()->id,
        'to_location_id' => StockLocation::factory()->for($company)->create()->id,
        ...$overrides['productLine'] ?? [],
    ]);

    return [
        'picking' => $picking,
        'stockMove' => $stockMove,
        'productLine' => $productLine,
        'product' => $product,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createControlPointForGoodsReceipt(
    Company $company,
    ?Product $product = null,
    array $overrides = []
): QualityControlPoint {
    $template = QualityInspectionTemplate::factory()->for($company)->create();

    return QualityControlPoint::factory()->for($company)->create([
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'trigger_frequency' => QualityTriggerFrequency::PerOperation,
        'product_id' => $product?->id,
        'inspection_template_id' => $template->id,
        'active' => true,
        'is_blocking' => false,
        ...$overrides,
    ]);
}

// ============================================================================
// Test: Quality check auto-creation when goods receipt is validated
// ============================================================================

test('creates quality check when goods receipt is validated with matching control point', function () {
    /** @var \Tests\TestCase $this */
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    // Create control point that applies to this product
    /** @var Company $company */
    /** @var Product $product */
    $controlPoint = createControlPointForGoodsReceipt($company, $product);

    // Create goods receipt
    $setup = createGoodsReceiptWithProductLine($company, $product, 10.0);
    $picking = $setup['picking'];

    // Dispatch the event
    StockPickingValidated::dispatch($picking, $user);

    // Assert quality check was created
    /** @phpstan-ignore-next-line */
    $this->assertDatabaseHas('quality_checks', [
        'company_id' => $company->id,
        'product_id' => $product->id,
        'source_type' => StockPicking::class,
        'source_id' => $picking->id,
        'status' => QualityCheckStatus::Draft->value,
    ]);
});

test('creates quality check with inspection template from control point', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    $template = QualityInspectionTemplate::factory()->for($company)->create();
    $controlPoint = QualityControlPoint::factory()->for($company)->create([
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'trigger_frequency' => QualityTriggerFrequency::PerOperation,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'active' => true,
    ]);

    $setup = createGoodsReceiptWithProductLine($company, $product);
    $picking = $setup['picking'];

    StockPickingValidated::dispatch($picking, $user);

    /** @var QualityCheck $qualityCheck */
    $qualityCheck = QualityCheck::where('source_id', $picking->id)
        ->where('source_type', StockPicking::class)
        ->first();

    expect($qualityCheck)
        ->not->toBeNull();

    if ($qualityCheck) {
        expect($qualityCheck->inspection_template_id)->toBe($template->id);
    }
});

// ============================================================================
// Test: No quality check when no control points exist
// ============================================================================

test('does not create quality check when no control points exist for product', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    // No control point created for this product

    $setup = createGoodsReceiptWithProductLine($company, $product);
    $picking = $setup['picking'];

    StockPickingValidated::dispatch($picking, $user);

    /** @phpstan-ignore-next-line */
    $this->assertDatabaseMissing('quality_checks', [
        'product_id' => $product->id,
        'source_type' => StockPicking::class,
        'source_id' => $picking->id,
    ]);
});

test('does not create quality check when control point is inactive', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    // Create inactive control point
    createControlPointForGoodsReceipt($company, $product, ['active' => false]);

    $setup = createGoodsReceiptWithProductLine($company, $product);
    $picking = $setup['picking'];

    StockPickingValidated::dispatch($picking, $user);

    /** @phpstan-ignore-next-line */
    $this->assertDatabaseMissing('quality_checks', [
        'product_id' => $product->id,
    ]);
});

test('does not create quality check when control point is for different operation', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    // Create control point for internal transfer, not goods receipt
    $template = QualityInspectionTemplate::factory()->for($company)->create();
    QualityControlPoint::factory()->for($company)->create([
        'trigger_operation' => QualityTriggerOperation::InternalTransfer,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'active' => true,
    ]);

    $setup = createGoodsReceiptWithProductLine($company, $product);
    $picking = $setup['picking'];

    StockPickingValidated::dispatch($picking, $user);

    /** @phpstan-ignore-next-line */
    $this->assertDatabaseMissing('quality_checks', [
        'product_id' => $product->id,
    ]);
});

// ============================================================================
// Test: Quality check per lot for lot-tracked products
// ============================================================================

test('creates one quality check per lot for lot-tracked products', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::Lot,
    ]);

    $controlPoint = createControlPointForGoodsReceipt($company, $product);

    $fromLocation = StockLocation::factory()->for($company)->create();
    $toLocation = StockLocation::factory()->for($company)->create();

    $picking = StockPicking::factory()->for($company)->receipt()->done()->create();

    $stockMove = StockMove::factory()->for($company)->receipt()->done()->create([
        'picking_id' => $picking->id,
    ]);

    $productLine = StockMoveProductLine::factory()->for($company)->create([
        'stock_move_id' => $stockMove->id,
        'product_id' => $product->id,
        'quantity' => 100,
        'from_location_id' => $fromLocation->id,
        'to_location_id' => $toLocation->id,
    ]);

    // Create two lots with stock move lines
    $lot1 = Lot::factory()->for($company)->create(['product_id' => $product->id]);
    $lot2 = Lot::factory()->for($company)->create(['product_id' => $product->id]);

    $productLine->stockMoveLines()->createMany([
        [
            'company_id' => $company->id,
            'lot_id' => $lot1->id,
            'quantity' => 50,
        ],
        [
            'company_id' => $company->id,
            'lot_id' => $lot2->id,
            'quantity' => 50,
        ],
    ]);

    StockPickingValidated::dispatch($picking, $user);

    // Should create one quality check per lot
    $checks = QualityCheck::where('source_id', $picking->id)
        ->where('source_type', StockPicking::class)
        ->get();

    expect($checks)->toHaveCount(2);
    /** @var array<int> $lotIds */
    $lotIds = $checks->pluck('lot_id')->toArray();
    expect(array_diff([$lot1->id, $lot2->id], $lotIds))->toBeEmpty();
});

// ============================================================================
// Test: Quality check per serial number for serial-tracked products
// ============================================================================

test('creates one quality check per serial number for serial-tracked products', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::Serial,
    ]);

    $controlPoint = createControlPointForGoodsReceipt($company, $product);

    $fromLocation = StockLocation::factory()->for($company)->create();
    $toLocation = StockLocation::factory()->for($company)->create();

    $picking = StockPicking::factory()->for($company)->receipt()->done()->create();

    $stockMove = StockMove::factory()->for($company)->receipt()->done()->create([
        'picking_id' => $picking->id,
    ]);

    $productLine = StockMoveProductLine::factory()->for($company)->create([
        'stock_move_id' => $stockMove->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'from_location_id' => $fromLocation->id,
        'to_location_id' => $toLocation->id,
    ]);

    // Create three serial numbers with stock move lines
    $serial1 = SerialNumber::factory()->for($company)->for($product)->create();
    $serial2 = SerialNumber::factory()->for($company)->for($product)->create();
    $serial3 = SerialNumber::factory()->for($company)->for($product)->create();

    $productLine->stockMoveLines()->createMany([
        [
            'company_id' => $company->id,
            'serial_number_id' => $serial1->id,
            'quantity' => 1,
        ],
        [
            'company_id' => $company->id,
            'serial_number_id' => $serial2->id,
            'quantity' => 1,
        ],
        [
            'company_id' => $company->id,
            'serial_number_id' => $serial3->id,
            'quantity' => 1,
        ],
    ]);

    StockPickingValidated::dispatch($picking, $user);

    $checks = QualityCheck::where('source_id', $picking->id)
        ->where('source_type', StockPicking::class)
        ->get();

    expect($checks)->toHaveCount(3);
    /** @var array<int> $serialIds */
    $serialIds = $checks->pluck('serial_number_id')->toArray();
    expect(array_diff([$serial1->id, $serial2->id, $serial3->id], $serialIds))->toBeEmpty();
});

// ============================================================================
// Test: Single quality check for non-tracked products
// ============================================================================

test('creates single quality check for non-tracked products', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    $controlPoint = createControlPointForGoodsReceipt($company, $product);

    $setup = createGoodsReceiptWithProductLine($company, $product, 100);
    $picking = $setup['picking'];

    StockPickingValidated::dispatch($picking, $user);

    $checks = QualityCheck::where('source_id', $picking->id)
        ->where('source_type', StockPicking::class)
        ->get();

    expect($checks)->toHaveCount(1);
    $firstCheck = $checks->first();
    if ($firstCheck) {
        expect($firstCheck->lot_id)->toBeNull();
        expect($firstCheck->serial_number_id)->toBeNull();
    }
});

// ============================================================================
// Test: Multiple control points trigger multiple checks
// ============================================================================

test('creates multiple quality checks when multiple control points trigger', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    // Create two different control points for the same product
    $template1 = QualityInspectionTemplate::factory()->for($company)->create(['name' => 'Visual Inspection']);
    $template2 = QualityInspectionTemplate::factory()->for($company)->create(['name' => 'Measurement Check']);

    QualityControlPoint::factory()->for($company)->create([
        'name' => 'Visual Check Point',
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'trigger_frequency' => QualityTriggerFrequency::PerOperation,
        'product_id' => $product->id,
        'inspection_template_id' => $template1->id,
        'active' => true,
    ]);

    QualityControlPoint::factory()->for($company)->create([
        'name' => 'Measurement Check Point',
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'trigger_frequency' => QualityTriggerFrequency::PerOperation,
        'product_id' => $product->id,
        'inspection_template_id' => $template2->id,
        'active' => true,
    ]);

    $setup = createGoodsReceiptWithProductLine($company, $product);
    $picking = $setup['picking'];

    StockPickingValidated::dispatch($picking, $user);

    $checks = QualityCheck::where('source_id', $picking->id)
        ->where('source_type', StockPicking::class)
        ->get();

    expect($checks)->toHaveCount(2);
    expect($checks->pluck('inspection_template_id')->sort()->values()->toArray())
        ->toBe([$template1->id, $template2->id]);
});

// ============================================================================
// Test: Internal transfer triggers quality checks
// ============================================================================

test('creates quality check when internal transfer is validated with matching control point', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    // Create control point for internal transfer
    $template = QualityInspectionTemplate::factory()->for($company)->create();
    QualityControlPoint::factory()->for($company)->create([
        'trigger_operation' => QualityTriggerOperation::InternalTransfer,
        'trigger_frequency' => QualityTriggerFrequency::PerOperation,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'active' => true,
    ]);

    $fromLocation = StockLocation::factory()->for($company)->create();
    $toLocation = StockLocation::factory()->for($company)->create();

    // Create internal transfer picking
    $picking = StockPicking::factory()->for($company)->internal()->done()->create();

    $stockMove = StockMove::factory()->for($company)->create([
        'picking_id' => $picking->id,
        'move_type' => StockMoveType::InternalTransfer,
        'status' => StockMoveStatus::Done,
    ]);

    StockMoveProductLine::factory()->for($company)->create([
        'stock_move_id' => $stockMove->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'from_location_id' => $fromLocation->id,
        'to_location_id' => $toLocation->id,
    ]);

    StockPickingValidated::dispatch($picking, $user);

    /** @phpstan-ignore-next-line */
    $this->assertDatabaseHas('quality_checks', [
        'company_id' => $company->id,
        'product_id' => $product->id,
        'source_type' => StockPicking::class,
        'source_id' => $picking->id,
    ]);
});

// ============================================================================
// Test: Edge cases
// ============================================================================

test('handles empty stock moves gracefully', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    createControlPointForGoodsReceipt($company, $product);

    // Create picking with no stock moves
    $picking = StockPicking::factory()->for($company)->receipt()->done()->create();

    // This should not throw an exception
    StockPickingValidated::dispatch($picking, $user);

    /** @phpstan-ignore-next-line */
    $this->assertDatabaseMissing('quality_checks', [
        'source_id' => $picking->id,
    ]);
});

test('handles stock moves with no product lines gracefully', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    createControlPointForGoodsReceipt($company, $product);

    $picking = StockPicking::factory()->for($company)->receipt()->done()->create();

    // Create stock move with no product lines
    StockMove::factory()->for($company)->receipt()->done()->create([
        'picking_id' => $picking->id,
    ]);

    // This should not throw an exception
    StockPickingValidated::dispatch($picking, $user);

    /** @phpstan-ignore-next-line */
    $this->assertDatabaseMissing('quality_checks', [
        'source_id' => $picking->id,
    ]);
});

test('does not create quality check for delivery picking type', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    // Even with goods receipt control point, delivery should not trigger
    createControlPointForGoodsReceipt($company, $product);

    $fromLocation = StockLocation::factory()->for($company)->create();
    $toLocation = StockLocation::factory()->for($company)->create();

    // Create delivery picking
    $picking = StockPicking::factory()->for($company)->delivery()->done()->create();

    $stockMove = StockMove::factory()->for($company)->delivery()->done()->create([
        'picking_id' => $picking->id,
    ]);

    StockMoveProductLine::factory()->for($company)->create([
        'stock_move_id' => $stockMove->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'from_location_id' => $fromLocation->id,
        'to_location_id' => $toLocation->id,
    ]);

    StockPickingValidated::dispatch($picking, $user);

    /** @phpstan-ignore-next-line */
    $this->assertDatabaseMissing('quality_checks', [
        'source_id' => $picking->id,
    ]);
});

test('respects quantity threshold for per-quantity control points', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product */
    $product = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    $template = QualityInspectionTemplate::factory()->for($company)->create();

    // Control point triggers only for quantities >= 100
    QualityControlPoint::factory()->for($company)->create([
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'trigger_frequency' => QualityTriggerFrequency::PerQuantity,
        'product_id' => $product->id,
        'inspection_template_id' => $template->id,
        'quantity_threshold' => 100,
        'active' => true,
    ]);

    // Receipt with quantity below threshold (50)
    $setup1 = createGoodsReceiptWithProductLine($company, $product, 50);
    StockPickingValidated::dispatch($setup1['picking'], $user);

    /** @phpstan-ignore-next-line */
    $this->assertDatabaseMissing('quality_checks', [
        'source_id' => $setup1['picking']->id,
    ]);

    // Receipt with quantity at threshold (100)
    $setup2 = createGoodsReceiptWithProductLine($company, $product, 100);
    StockPickingValidated::dispatch($setup2['picking'], $user);

    /** @phpstan-ignore-next-line */
    $this->assertDatabaseHas('quality_checks', [
        'source_id' => $setup2['picking']->id,
    ]);
});

test('control point with null product_id applies to all products', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product1 */
    $product1 = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);
    /** @var Product $product2 */
    $product2 = Product::factory()->for($company)->create([
        'tracking_type' => TrackingType::None,
    ]);

    // Control point that applies to all products (null product_id)
    $template = QualityInspectionTemplate::factory()->for($company)->create();
    QualityControlPoint::factory()->for($company)->create([
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'trigger_frequency' => QualityTriggerFrequency::PerOperation,
        'product_id' => null, // Applies to all
        'inspection_template_id' => $template->id,
        'active' => true,
    ]);

    // Receipt with product1
    $setup1 = createGoodsReceiptWithProductLine($company, $product1);
    StockPickingValidated::dispatch($setup1['picking'], $user);

    /** @phpstan-ignore-next-line */
    $this->assertDatabaseHas('quality_checks', [
        'product_id' => $product1->id,
        'source_id' => $setup1['picking']->id,
    ]);

    // Receipt with product2
    $setup2 = createGoodsReceiptWithProductLine($company, $product2);
    StockPickingValidated::dispatch($setup2['picking'], $user);

    /** @phpstan-ignore-next-line */
    $this->assertDatabaseHas('quality_checks', [
        'product_id' => $product2->id,
        'source_id' => $setup2['picking']->id,
    ]);
});

test('creates quality checks for multiple products in same picking', function () {
    /** @var \Tests\TestCase $this */
    /** @var Company $company */
    $company = Company::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);

    /** @var Product $product1 */
    $product1 = Product::factory()->for($company)->create([
        'name' => 'Product A',
        'tracking_type' => TrackingType::None,
    ]);
    /** @var Product $product2 */
    $product2 = Product::factory()->for($company)->create([
        'name' => 'Product B',
        'tracking_type' => TrackingType::None,
    ]);

    // Control point that applies to all products
    $template = QualityInspectionTemplate::factory()->for($company)->create();
    QualityControlPoint::factory()->for($company)->create([
        'trigger_operation' => QualityTriggerOperation::GoodsReceipt,
        'trigger_frequency' => QualityTriggerFrequency::PerOperation,
        'product_id' => null,
        'inspection_template_id' => $template->id,
        'active' => true,
    ]);

    $fromLocation = StockLocation::factory()->for($company)->create();
    $toLocation = StockLocation::factory()->for($company)->create();

    $picking = StockPicking::factory()->for($company)->receipt()->done()->create();

    // Create stock move with product line for product1
    $stockMove1 = StockMove::factory()->for($company)->receipt()->done()->create([
        'picking_id' => $picking->id,
    ]);
    StockMoveProductLine::factory()->for($company)->create([
        'stock_move_id' => $stockMove1->id,
        'product_id' => $product1->id,
        'quantity' => 10,
        'from_location_id' => $fromLocation->id,
        'to_location_id' => $toLocation->id,
    ]);

    // Create stock move with product line for product2
    $stockMove2 = StockMove::factory()->for($company)->receipt()->done()->create([
        'picking_id' => $picking->id,
    ]);
    StockMoveProductLine::factory()->for($company)->create([
        'stock_move_id' => $stockMove2->id,
        'product_id' => $product2->id,
        'quantity' => 5,
        'from_location_id' => $fromLocation->id,
        'to_location_id' => $toLocation->id,
    ]);

    StockPickingValidated::dispatch($picking, $user);

    $checks = QualityCheck::where('source_id', $picking->id)
        ->where('source_type', StockPicking::class)
        ->get();

    expect($checks)->toHaveCount(2);
    expect($checks->pluck('product_id')->sort()->values()->toArray())
        ->toBe([$product1->id, $product2->id]);
});

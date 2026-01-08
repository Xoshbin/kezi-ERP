<?php

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Modules\Inventory\Models\StockLocation;
use Modules\Manufacturing\DataTransferObjects\BOMLineDTO;
use Modules\Manufacturing\DataTransferObjects\CreateBOMDTO;
use Modules\Manufacturing\DataTransferObjects\CreateManufacturingOrderDTO;
use Modules\Manufacturing\Enums\BOMType;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Manufacturing\Models\WorkCenter;
use Modules\Manufacturing\Services\BOMService;
use Modules\Manufacturing\Services\ManufacturingOrderService;
use Modules\Product\Models\Product;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->bomService = app(BOMService::class);
    $this->moService = app(ManufacturingOrderService::class);

    // Create stock locations
    $this->sourceLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'internal',
    ]);
    $this->destinationLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'internal',
    ]);
});

it('creates a manufacturing order from a BOM', function () {
    $finishedProduct = Product::factory()->create(['company_id' => $this->company->id]);
    $component = Product::factory()->create(['company_id' => $this->company->id]);

    // Create BOM
    $bomDTO = new CreateBOMDTO(
        companyId: $this->company->id,
        productId: $finishedProduct->id,
        code: 'BOM-MO-001',
        name: ['en' => 'MO Test BOM'],
        type: BOMType::Normal,
        quantity: 1.0,
        lines: [
            new BOMLineDTO(
                productId: $component->id,
                quantity: 2.0,
                unitCost: Money::of(10, $this->company->currency->code),
            ),
        ],
    );

    $bom = $this->bomService->create($bomDTO);

    // Create MO
    $moDTO = new CreateManufacturingOrderDTO(
        companyId: $this->company->id,
        bomId: $bom->id,
        productId: $finishedProduct->id,
        quantityToProduce: 5.0,
        sourceLocationId: $this->sourceLocation->id,
        destinationLocationId: $this->destinationLocation->id,
    );

    $mo = $this->moService->create($moDTO);

    expect($mo)
        ->toBeInstanceOf(ManufacturingOrder::class)
        ->number->toStartWith('MO')
        ->status->toBe(ManufacturingOrderStatus::Draft)
        ->quantity_to_produce->toBe('5.0000')
        ->quantity_produced->toBe('0.0000')
        ->lines->toHaveCount(1);

    // Check MO line quantities are scaled
    expect($mo->lines->first())
        ->product_id->toBe($component->id)
        ->quantity_required->toBe('10.0000') // 2.0 * 5.0
        ->quantity_consumed->toBe('0.0000');
});

it('confirms a manufacturing order and creates work orders', function () {
    $finishedProduct = Product::factory()->create(['company_id' => $this->company->id]);
    $component = Product::factory()->create(['company_id' => $this->company->id]);

    $workCenter = WorkCenter::factory()->create([
        'company_id' => $this->company->id,
    ]);

    // Create BOM with work center
    $bomDTO = new CreateBOMDTO(
        companyId: $this->company->id,
        productId: $finishedProduct->id,
        code: 'BOM-CONFIRM-001',
        name: ['en' => 'Confirm Test BOM'],
        type: BOMType::Normal,
        quantity: 1.0,
        lines: [
            new BOMLineDTO(
                productId: $component->id,
                quantity: 1.0,
                unitCost: Money::of(10, $this->company->currency->code),
                workCenterId: $workCenter->id,
            ),
        ],
    );

    $bom = $this->bomService->create($bomDTO);

    $moDTO = new CreateManufacturingOrderDTO(
        companyId: $this->company->id,
        bomId: $bom->id,
        productId: $finishedProduct->id,
        quantityToProduce: 1.0,
        sourceLocationId: $this->sourceLocation->id,
        destinationLocationId: $this->destinationLocation->id,
    );

    $mo = $this->moService->create($moDTO);
    $mo = $this->moService->confirm($mo);

    expect($mo->status)->toBe(ManufacturingOrderStatus::Confirmed);
    expect($mo->workOrders)->toHaveCount(1);
    expect($mo->workOrders->first())
        ->work_center_id->toBe($workCenter->id)
        ->status->toBeString('pending');
});

it('starts production and updates status', function () {
    $finishedProduct = Product::factory()->create(['company_id' => $this->company->id]);
    $component = Product::factory()->create(['company_id' => $this->company->id]);

    $bomDTO = new CreateBOMDTO(
        companyId: $this->company->id,
        productId: $finishedProduct->id,
        code: 'BOM-START-001',
        name: ['en' => 'Start Test BOM'],
        type: BOMType::Normal,
        quantity: 1.0,
        lines: [
            new BOMLineDTO(
                productId: $component->id,
                quantity: 1.0,
                unitCost: Money::of(10, $this->company->currency->code),
            ),
        ],
    );

    $bom = $this->bomService->create($bomDTO);

    $moDTO = new CreateManufacturingOrderDTO(
        companyId: $this->company->id,
        bomId: $bom->id,
        productId: $finishedProduct->id,
        quantityToProduce: 1.0,
        sourceLocationId: $this->sourceLocation->id,
        destinationLocationId: $this->destinationLocation->id,
    );

    $mo = $this->moService->create($moDTO);
    $mo = $this->moService->confirm($mo);
    $mo = $this->moService->startProduction($mo);

    expect($mo->status)->toBe(ManufacturingOrderStatus::InProgress);
    expect($mo->actual_start_date)->toBeInstanceOf(Carbon::class);
});

it('validates status transitions', function () {
    $finishedProduct = Product::factory()->create(['company_id' => $this->company->id]);
    $component = Product::factory()->create(['company_id' => $this->company->id]);

    $bomDTO = new CreateBOMDTO(
        companyId: $this->company->id,
        productId: $finishedProduct->id,
        code: 'BOM-VALIDATE-001',
        name: ['en' => 'Validation Test BOM'],
        type: BOMType::Normal,
        quantity: 1.0,
        lines: [
            new BOMLineDTO(
                productId: $component->id,
                quantity: 1.0,
                unitCost: Money::of(10, $this->company->currency->code),
            ),
        ],
    );

    $bom = $this->bomService->create($bomDTO);

    $moDTO = new CreateManufacturingOrderDTO(
        companyId: $this->company->id,
        bomId: $bom->id,
        productId: $finishedProduct->id,
        quantityToProduce: 1.0,
        sourceLocationId: $this->sourceLocation->id,
        destinationLocationId: $this->destinationLocation->id,
    );

    $mo = $this->moService->create($moDTO);

    // Try to start production without confirming
    $this->moService->startProduction($mo);
})->throws(InvalidArgumentException::class, 'Only confirmed manufacturing orders can be started.');

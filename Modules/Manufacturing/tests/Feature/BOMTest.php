<?php

use App\Models\Company;
use Brick\Money\Money;
use Modules\Manufacturing\DataTransferObjects\BOMLineDTO;
use Modules\Manufacturing\DataTransferObjects\CreateBOMDTO;
use Modules\Manufacturing\Enums\BOMType;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Manufacturing\Services\BOMService;
use Modules\Product\Models\Product;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->bomService = app(BOMService::class);
});

it('creates a BOM with component lines', function () {
    $finishedProduct = Product::factory()->create(['company_id' => $this->company->id]);
    $component1 = Product::factory()->create(['company_id' => $this->company->id]);
    $component2 = Product::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateBOMDTO(
        companyId: $this->company->id,
        productId: $finishedProduct->id,
        code: 'BOM-001',
        name: ['en' => 'Test BOM'],
        type: BOMType::Normal,
        quantity: 1.0,
        lines: [
            new BOMLineDTO(
                productId: $component1->id,
                quantity: 2.0,
                unitCost: Money::of(10, $this->company->currency->code),
            ),
            new BOMLineDTO(
                productId: $component2->id,
                quantity: 3.0,
                unitCost: Money::of(5, $this->company->currency->code),
            ),
        ],
    );

    $bom = $this->bomService->create($dto);

    expect($bom)
        ->toBeInstanceOf(BillOfMaterial::class)
        ->code->toBe('BOM-001')
        ->product_id->toBe($finishedProduct->id)
        ->type->toBe(BOMType::Normal)
        ->lines->toHaveCount(2);

    expect($bom->lines->first())
        ->product_id->toBe($component1->id)
        ->quantity->toBe('2.0000');
});

it('prevents circular BOM references', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateBOMDTO(
        companyId: $this->company->id,
        productId: $product->id,
        code: 'BOM-002',
        name: ['en' => 'Circular BOM'],
        type: BOMType::Normal,
        quantity: 1.0,
        lines: [
            new BOMLineDTO(
                productId: $product->id, // Same as finished product!
                quantity: 1.0,
                unitCost: Money::of(10, $this->company->currency->code),
            ),
        ],
    );

    $this->bomService->create($dto);
})->throws(InvalidArgumentException::class, 'A product cannot be a component of itself in a BOM.');

it('calculates total material cost correctly', function () {
    $finishedProduct = Product::factory()->create(['company_id' => $this->company->id]);
    $component1 = Product::factory()->create(['company_id' => $this->company->id]);
    $component2 = Product::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateBOMDTO(
        companyId: $this->company->id,
        productId: $finishedProduct->id,
        code: 'BOM-003',
        name: ['en' => 'Cost Test BOM'],
        type: BOMType::Normal,
        quantity: 1.0,
        lines: [
            new BOMLineDTO(
                productId: $component1->id,
                quantity: 2.0,
                unitCost: Money::of(10, $this->company->currency->code), // 2 * 10 = 20
            ),
            new BOMLineDTO(
                productId: $component2->id,
                quantity: 3.0,
                unitCost: Money::of(5, $this->company->currency->code), // 3 * 5 = 15
            ),
        ],
    );

    $bom = $this->bomService->create($dto);
    $totalCost = $this->bomService->calculateTotalMaterialCost($bom);

    expect($totalCost->getAmount()->toFloat())->toBe(35.0); // 20 + 15
});

it('supports different BOM types', function () {
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $component = Product::factory()->create(['company_id' => $this->company->id]);

    $kitBOM = new CreateBOMDTO(
        companyId: $this->company->id,
        productId: $product->id,
        code: 'BOM-KIT-001',
        name: ['en' => 'Kit BOM'],
        type: BOMType::Kit,
        quantity: 1.0,
        lines: [
            new BOMLineDTO(
                productId: $component->id,
                quantity: 1.0,
                unitCost: Money::of(10, $this->company->currency->code),
            ),
        ],
    );

    $bom = $this->bomService->create($kitBOM);

    expect($bom->type)->toBe(BOMType::Kit);
});

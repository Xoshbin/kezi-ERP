<?php

namespace Modules\Manufacturing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Manufacturing\Actions\CreateManufacturingOrderAction;
use Modules\Manufacturing\DataTransferObjects\CreateManufacturingOrderDTO;
use Modules\Manufacturing\Enums\BOMType;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Manufacturing\Models\BOMLine;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->user = \App\Models\User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);
});

it('recursively explodes Kit and Phantom BOMs in MO lines', function () {
    // 1. Setup Base Raw Materials
    $bolt = Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Bolt']);
    $leg = Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Leg']);
    $top = Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Table Top']);

    // 2. Create Kit BOM for Leg (1 Leg = 2 Bolts)
    $legBom = BillOfMaterial::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $leg->id,
        'type' => BOMType::Kit,
        'quantity' => 1,
    ]);
    BOMLine::factory()->create([
        'company_id' => $this->company->id,
        'bom_id' => $legBom->id,
        'product_id' => $bolt->id,
        'quantity' => 2,
        'unit_cost' => 1.5,
    ]);

    // 3. Create Phantom BOM for Table (1 Table = 4 Legs + 1 Top)
    $table = Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Table']);
    $tableBom = BillOfMaterial::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $table->id,
        'type' => BOMType::Phantom,
        'quantity' => 1,
    ]);
    // Component 1: Legs (which is a Kit)
    BOMLine::factory()->create([
        'company_id' => $this->company->id,
        'bom_id' => $tableBom->id,
        'product_id' => $leg->id,
        'quantity' => 4,
    ]);
    // Component 2: Table Top (Normal Raw Material)
    BOMLine::factory()->create([
        'company_id' => $this->company->id,
        'bom_id' => $tableBom->id,
        'product_id' => $top->id,
        'quantity' => 1,
        'unit_cost' => 50,
    ]);

    // 4. Create MO for Table (Quantity = 2)
    // 1 Table -> 4 Legs -> 8 Bolts
    // 2 Tables -> 8 Legs -> 16 Bolts
    // 2 Tables -> 2 Tops

    $dto = new CreateManufacturingOrderDTO(
        companyId: $this->company->id,
        bomId: $tableBom->id,
        productId: $table->id,
        quantityToProduce: 2,
        sourceLocationId: $this->company->default_stock_location_id,
        destinationLocationId: $this->company->default_stock_location_id,
    );

    $action = app(CreateManufacturingOrderAction::class);
    $mo = $action->execute($dto);

    // 5. Assertions
    // Should have 2 types of lines: Bolt and Table Top (Leg is exploded)
    expect($mo->lines)->toHaveCount(2);

    $boltLine = $mo->lines->where('product_id', $bolt->id)->first();
    $topLine = $mo->lines->where('product_id', $top->id)->first();

    expect($boltLine)->not->toBeNull();
    expect((float) $boltLine->quantity_required)->toBe(16.0); // 2 tables * 4 legs * 2 bolts
    expect((float) $boltLine->unit_cost->getAmount()->toFloat())->toBe(1.5);

    expect($topLine)->not->toBeNull();
    expect((float) $topLine->quantity_required)->toBe(2.0); // 2 tables * 1 top
    expect((float) $topLine->unit_cost->getAmount()->toFloat())->toBe(50.0);
});

it('does not explode Normal BOMs', function () {
    $subProduct = Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Sub-Assembly']);
    $rawMaterial = Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Raw Material']);

    // Normal BOM for Sub-Assembly
    $subBom = BillOfMaterial::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $subProduct->id,
        'type' => BOMType::Normal,
        'quantity' => 1,
    ]);
    BOMLine::factory()->create([
        'company_id' => $this->company->id,
        'bom_id' => $subBom->id,
        'product_id' => $rawMaterial->id,
        'quantity' => 5,
    ]);

    $parentProduct = Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Parent Product']);
    $parentBom = BillOfMaterial::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $parentProduct->id,
        'type' => BOMType::Normal,
        'quantity' => 1,
    ]);
    BOMLine::factory()->create([
        'company_id' => $this->company->id,
        'bom_id' => $parentBom->id,
        'product_id' => $subProduct->id,
        'quantity' => 2,
    ]);

    $dto = new CreateManufacturingOrderDTO(
        companyId: $this->company->id,
        bomId: $parentBom->id,
        productId: $parentProduct->id,
        quantityToProduce: 1,
        sourceLocationId: $this->company->default_stock_location_id,
        destinationLocationId: $this->company->default_stock_location_id,
    );

    $action = app(CreateManufacturingOrderAction::class);
    $mo = $action->execute($dto);

    // Should only have Sub-Assembly line, NOT raw material
    expect($mo->lines)->toHaveCount(1);
    expect($mo->lines->first()->product_id)->toBe($subProduct->id);
    expect((float) $mo->lines->first()->quantity_required)->toBe(2.0);
});

it('prevents infinite recursion with max depth guard', function () {
    $productA = Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Product A']);
    $productB = Product::factory()->create(['company_id' => $this->company->id, 'name' => 'Product B']);

    $bomA = BillOfMaterial::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $productA->id,
        'type' => BOMType::Phantom,
    ]);
    BOMLine::factory()->create([
        'company_id' => $this->company->id,
        'bom_id' => $bomA->id,
        'product_id' => $productB->id,
        'quantity' => 1,
    ]);

    $bomB = BillOfMaterial::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $productB->id,
        'type' => BOMType::Phantom,
    ]);
    BOMLine::factory()->create([
        'company_id' => $this->company->id,
        'bom_id' => $bomB->id,
        'product_id' => $productA->id,
        'quantity' => 1,
    ]);

    $dto = new CreateManufacturingOrderDTO(
        companyId: $this->company->id,
        bomId: $bomA->id,
        productId: $productA->id,
        quantityToProduce: 1,
        sourceLocationId: $this->company->default_stock_location_id,
        destinationLocationId: $this->company->default_stock_location_id,
    );

    $action = app(CreateManufacturingOrderAction::class);

    // It should eventually stop or throw an exception.
    // Usually, we want it to stop and maybe include the circular item as is or throw.
    // The requirement says "Implement a max-depth guard (e.g., 10 levels) to prevent circular dependency loops."

    expect(fn () => $action->execute($dto))->toThrow(\RuntimeException::class, 'Max BOM explosion depth reached');
});

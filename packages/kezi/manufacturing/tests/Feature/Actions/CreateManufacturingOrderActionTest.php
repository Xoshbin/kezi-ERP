<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Manufacturing\Actions\CreateManufacturingOrderAction;
use Kezi\Manufacturing\DataTransferObjects\CreateManufacturingOrderDTO;
use Kezi\Manufacturing\Enums\ManufacturingOrderStatus;
use Kezi\Manufacturing\Models\BillOfMaterial;
use Kezi\Manufacturing\Models\BOMLine;
use Kezi\Manufacturing\Models\ManufacturingOrder;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Create locations for manufacturing
    $this->sourceLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
    ]);

    $this->destinationLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
    ]);
});

describe('CreateManufacturingOrderAction', function () {
    it('creates a manufacturing order from a BOM', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Laptop'],
            'type' => ProductType::Storable,
        ]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
            'code' => 'BOM-LAPTOP-001',
        ]);

        $dto = new CreateManufacturingOrderDTO(
            companyId: $this->company->id,
            bomId: $bom->id,
            productId: $finishedProduct->id,
            quantityToProduce: 10.0,
            sourceLocationId: $this->sourceLocation->id,
            destinationLocationId: $this->destinationLocation->id,
            plannedStartDate: Carbon::now(),
            plannedEndDate: Carbon::now()->addDays(5),
            notes: 'Urgent order for customer',
        );

        // Act
        $action = app(CreateManufacturingOrderAction::class);
        $mo = $action->execute($dto);

        // Assert
        expect($mo)->toBeInstanceOf(ManufacturingOrder::class);
        expect($mo->company_id)->toBe($this->company->id);
        expect($mo->bom_id)->toBe($bom->id);
        expect($mo->product_id)->toBe($finishedProduct->id);
        expect((float) $mo->quantity_to_produce)->toBe(10.0);
        expect((float) $mo->quantity_produced)->toBe(0.0);
        expect($mo->status)->toBe(ManufacturingOrderStatus::Draft);
        expect($mo->source_location_id)->toBe($this->sourceLocation->id);
        expect($mo->destination_location_id)->toBe($this->destinationLocation->id);
        expect($mo->notes)->toBe('Urgent order for customer');
    });

    it('generates unique MO number', function () {
        // Arrange
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $dto = new CreateManufacturingOrderDTO(
            companyId: $this->company->id,
            bomId: $bom->id,
            productId: $bom->product_id,
            quantityToProduce: 5.0,
            sourceLocationId: $this->sourceLocation->id,
            destinationLocationId: $this->destinationLocation->id,
        );

        // Act
        $action = app(CreateManufacturingOrderAction::class);
        $mo1 = $action->execute($dto);
        $mo2 = $action->execute($dto);

        // Assert
        expect($mo1->number)->toStartWith('MO');
        expect($mo2->number)->toStartWith('MO');
        expect($mo1->number)->not->toBe($mo2->number);
    });

    it('copies BOM lines to manufacturing order lines', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Desktop Computer'],
        ]);

        $component1 = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Motherboard'],
        ]);

        $component2 = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Power Supply'],
        ]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
        ]);

        // Create BOM lines
        $currencyCode = $this->company->currency->code;

        BOMLine::factory()->forBom($bom)->create([
            'product_id' => $component1->id,
            'quantity' => 1.0,
            'unit_cost' => 150000,
            'currency_code' => $currencyCode,
        ]);

        BOMLine::factory()->forBom($bom)->create([
            'product_id' => $component2->id,
            'quantity' => 1.0,
            'unit_cost' => 75000,
            'currency_code' => $currencyCode,
        ]);

        $dto = new CreateManufacturingOrderDTO(
            companyId: $this->company->id,
            bomId: $bom->id,
            productId: $finishedProduct->id,
            quantityToProduce: 5.0, // Produce 5 units
            sourceLocationId: $this->sourceLocation->id,
            destinationLocationId: $this->destinationLocation->id,
        );

        // Act
        $action = app(CreateManufacturingOrderAction::class);
        $mo = $action->execute($dto);

        // Assert
        expect($mo->lines)->toHaveCount(2);

        // Quantities should be multiplied by quantity to produce
        $motherboardLine = $mo->lines->where('product_id', $component1->id)->first();
        expect($motherboardLine)->not->toBeNull();
        expect((float) $motherboardLine->quantity_required)->toBe(5.0); // 1 * 5 = 5
        expect((float) $motherboardLine->quantity_consumed)->toBe(0.0);

        $powerSupplyLine = $mo->lines->where('product_id', $component2->id)->first();
        expect($powerSupplyLine)->not->toBeNull();
        expect((float) $powerSupplyLine->quantity_required)->toBe(5.0); // 1 * 5 = 5
    });

    it('creates MO in draft status', function () {
        // Arrange
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $dto = new CreateManufacturingOrderDTO(
            companyId: $this->company->id,
            bomId: $bom->id,
            productId: $bom->product_id,
            quantityToProduce: 1.0,
            sourceLocationId: $this->sourceLocation->id,
            destinationLocationId: $this->destinationLocation->id,
        );

        // Act
        $action = app(CreateManufacturingOrderAction::class);
        $mo = $action->execute($dto);

        // Assert
        expect($mo->status)->toBe(ManufacturingOrderStatus::Draft);
        expect($mo->actual_start_date)->toBeNull();
        expect($mo->actual_end_date)->toBeNull();
    });

    it('persists MO and lines to database', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $component = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
        ]);

        BOMLine::factory()->forBom($bom)->create([
            'product_id' => $component->id,
            'quantity' => 3.0,
            'unit_cost' => 10000,
            'currency_code' => $this->company->currency->code,
        ]);

        $dto = new CreateManufacturingOrderDTO(
            companyId: $this->company->id,
            bomId: $bom->id,
            productId: $finishedProduct->id,
            quantityToProduce: 2.0,
            sourceLocationId: $this->sourceLocation->id,
            destinationLocationId: $this->destinationLocation->id,
        );

        // Act
        $action = app(CreateManufacturingOrderAction::class);
        $mo = $action->execute($dto);

        // Assert - verify database
        $this->assertDatabaseHas('manufacturing_orders', [
            'id' => $mo->id,
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'status' => ManufacturingOrderStatus::Draft->value,
        ]);

        $this->assertDatabaseHas('manufacturing_order_lines', [
            'manufacturing_order_id' => $mo->id,
            'product_id' => $component->id,
        ]);
    });

    it('loads relationships after creation', function () {
        // Arrange
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
        ]);

        BOMLine::factory()->forBom($bom)->create([
            'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
            'quantity' => 1.0,
            'unit_cost' => 5000,
            'currency_code' => $this->company->currency->code,
        ]);

        $dto = new CreateManufacturingOrderDTO(
            companyId: $this->company->id,
            bomId: $bom->id,
            productId: $bom->product_id,
            quantityToProduce: 1.0,
            sourceLocationId: $this->sourceLocation->id,
            destinationLocationId: $this->destinationLocation->id,
        );

        // Act
        $action = app(CreateManufacturingOrderAction::class);
        $mo = $action->execute($dto);

        // Assert - relationships should be loaded
        expect($mo->relationLoaded('lines'))->toBeTrue();
        expect($mo->relationLoaded('billOfMaterial'))->toBeTrue();
        expect($mo->relationLoaded('product'))->toBeTrue();
    });
});

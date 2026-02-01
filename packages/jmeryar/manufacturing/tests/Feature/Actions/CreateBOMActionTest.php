<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Manufacturing\Actions\CreateBOMAction;
use Jmeryar\Manufacturing\DataTransferObjects\BOMLineDTO;
use Jmeryar\Manufacturing\DataTransferObjects\CreateBOMDTO;
use Jmeryar\Manufacturing\Enums\BOMType;
use Jmeryar\Manufacturing\Models\BillOfMaterial;
use Jmeryar\Manufacturing\Models\WorkCenter;
use Jmeryar\Product\Enums\Products\ProductType;
use Jmeryar\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('CreateBOMAction', function () {
    it('creates a BOM with basic information', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Laptop'],
            'type' => ProductType::Storable,
        ]);

        $dto = new CreateBOMDTO(
            companyId: $this->company->id,
            productId: $finishedProduct->id,
            code: 'BOM-LAPTOP-001',
            name: ['en' => 'Laptop Assembly BOM'],
            type: BOMType::Normal,
            quantity: 1.0,
            lines: [],
            isActive: true,
            notes: 'Standard laptop assembly BOM',
        );

        // Act
        $action = app(CreateBOMAction::class);
        $bom = $action->execute($dto);

        // Assert
        expect($bom)->toBeInstanceOf(BillOfMaterial::class);
        expect($bom->company_id)->toBe($this->company->id);
        expect($bom->product_id)->toBe($finishedProduct->id);
        expect($bom->code)->toBe('BOM-LAPTOP-001');
        expect($bom->name)->toBe('Laptop Assembly BOM');
        expect($bom->type)->toBe(BOMType::Normal);
        expect((float) $bom->quantity)->toBe(1.0);
        expect($bom->is_active)->toBeTrue();
        expect($bom->notes)->toBe('Standard laptop assembly BOM');
    });

    it('creates a BOM with component lines', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Desktop Computer'],
            'type' => ProductType::Storable,
        ]);

        $motherboard = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Motherboard'],
            'type' => ProductType::Storable,
        ]);

        $cpu = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'CPU'],
            'type' => ProductType::Storable,
        ]);

        $ram = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'RAM Module'],
            'type' => ProductType::Storable,
        ]);

        $currencyCode = $this->company->currency->code;

        $dto = new CreateBOMDTO(
            companyId: $this->company->id,
            productId: $finishedProduct->id,
            code: 'BOM-DESKTOP-001',
            name: ['en' => 'Desktop Computer Assembly'],
            type: BOMType::Normal,
            quantity: 1.0,
            lines: [
                new BOMLineDTO(
                    productId: $motherboard->id,
                    quantity: 1.0,
                    unitCost: Money::of(150000, $currencyCode),
                ),
                new BOMLineDTO(
                    productId: $cpu->id,
                    quantity: 1.0,
                    unitCost: Money::of(300000, $currencyCode),
                ),
                new BOMLineDTO(
                    productId: $ram->id,
                    quantity: 2.0, // 2 RAM modules
                    unitCost: Money::of(50000, $currencyCode),
                ),
            ],
        );

        // Act
        $action = app(CreateBOMAction::class);
        $bom = $action->execute($dto);

        // Reload with proper relationships for Money cast to work
        $bom->load(['lines.company.currency']);

        // Assert
        expect($bom)->toBeInstanceOf(BillOfMaterial::class);
        expect($bom->lines)->toHaveCount(3);

        // Verify motherboard line
        $motherboardLine = $bom->lines->where('product_id', $motherboard->id)->first();
        expect($motherboardLine)->not->toBeNull();
        expect((float) $motherboardLine->quantity)->toBe(1.0);
        expect($motherboardLine->unit_cost)->toBeInstanceOf(Money::class);
        expect($motherboardLine->unit_cost->isEqualTo(Money::of(150000, $currencyCode)))->toBeTrue();

        // Verify CPU line
        $cpuLine = $bom->lines->where('product_id', $cpu->id)->first();
        expect($cpuLine)->not->toBeNull();
        expect((float) $cpuLine->quantity)->toBe(1.0);
        expect($cpuLine->unit_cost)->toBeInstanceOf(Money::class);
        expect($cpuLine->unit_cost->isEqualTo(Money::of(300000, $currencyCode)))->toBeTrue();

        // Verify RAM line (2 modules)
        $ramLine = $bom->lines->where('product_id', $ram->id)->first();
        expect($ramLine)->not->toBeNull();
        expect((float) $ramLine->quantity)->toBe(2.0);
        expect($ramLine->unit_cost)->toBeInstanceOf(Money::class);
        expect($ramLine->unit_cost->isEqualTo(Money::of(50000, $currencyCode)))->toBeTrue();
    });

    it('creates a BOM with work center assignment', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Assembled Product'],
            'type' => ProductType::Storable,
        ]);

        $component = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Component'],
            'type' => ProductType::Storable,
        ]);

        $workCenter = WorkCenter::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Assembly Line 1'],
            'code' => 'WC-ASM-01',
        ]);

        $currencyCode = $this->company->currency->code;

        $dto = new CreateBOMDTO(
            companyId: $this->company->id,
            productId: $finishedProduct->id,
            code: 'BOM-WC-001',
            name: ['en' => 'BOM with Work Center'],
            type: BOMType::Normal,
            quantity: 1.0,
            lines: [
                new BOMLineDTO(
                    productId: $component->id,
                    quantity: 1.0,
                    unitCost: Money::of(10000, $currencyCode),
                    workCenterId: $workCenter->id,
                ),
            ],
        );

        // Act
        $action = app(CreateBOMAction::class);
        $bom = $action->execute($dto);

        // Assert
        expect($bom->lines)->toHaveCount(1);
        expect($bom->lines->first()->work_center_id)->toBe($workCenter->id);
    });

    it('creates inactive BOM when isActive is false', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $dto = new CreateBOMDTO(
            companyId: $this->company->id,
            productId: $finishedProduct->id,
            code: 'BOM-INACTIVE-001',
            name: ['en' => 'Inactive BOM'],
            type: BOMType::Normal,
            quantity: 1.0,
            lines: [],
            isActive: false,
        );

        // Act
        $action = app(CreateBOMAction::class);
        $bom = $action->execute($dto);

        // Assert
        expect($bom->is_active)->toBeFalse();
    });

    it('persists BOM and lines to database', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $component = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $currencyCode = $this->company->currency->code;

        $dto = new CreateBOMDTO(
            companyId: $this->company->id,
            productId: $finishedProduct->id,
            code: 'BOM-DB-001',
            name: ['en' => 'Database Test BOM'],
            type: BOMType::Normal,
            quantity: 10.0,
            lines: [
                new BOMLineDTO(
                    productId: $component->id,
                    quantity: 5.0,
                    unitCost: Money::of(25000, $currencyCode),
                ),
            ],
        );

        // Act
        $action = app(CreateBOMAction::class);
        $bom = $action->execute($dto);

        // Assert - verify database
        $this->assertDatabaseHas('bills_of_materials', [
            'id' => $bom->id,
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
            'code' => 'BOM-DB-001',
        ]);

        $this->assertDatabaseHas('bom_lines', [
            'bom_id' => $bom->id,
            'product_id' => $component->id,
        ]);
    });
});

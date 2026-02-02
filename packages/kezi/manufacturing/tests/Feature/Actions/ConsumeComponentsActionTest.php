<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Manufacturing\Actions\ConsumeComponentsAction;
use Kezi\Manufacturing\Enums\ManufacturingOrderStatus;
use Kezi\Manufacturing\Models\BillOfMaterial;
use Kezi\Manufacturing\Models\ManufacturingOrder;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->sourceLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
        'name' => 'Raw Materials Warehouse',
    ]);

    $this->destinationLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
        'name' => 'Finished Goods Warehouse',
    ]);

    // Setup Accounts for accounting integration
    $this->rmAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1010',
        'name' => 'Raw Materials',
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::CurrentAssets,
    ]);

    $this->wipAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1030',
        'name' => 'WIP',
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::CurrentAssets,
    ]);

    $this->manufacturingJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Manufacturing Operations',
        'short_code' => 'MFG',
        'type' => \Kezi\Accounting\Enums\Accounting\JournalType::Miscellaneous,
    ]);

    // Configure Company Defaults
    $this->company->update([
        'default_manufacturing_journal_id' => $this->manufacturingJournal->id,
        'default_raw_materials_inventory_id' => $this->rmAccount->id,
        'default_wip_account_id' => $this->wipAccount->id,
    ]);
});

describe('ConsumeComponentsAction', function () {
    it('consumes components for an in-progress manufacturing order', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Assembled Product'],
            'type' => ProductType::Storable,
        ]);

        $component = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Component A'],
            'type' => ProductType::Storable,
        ]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $finishedProduct->id,
            'status' => ManufacturingOrderStatus::InProgress,
            'source_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
            'quantity_to_produce' => 10,
        ]);

        // Create MO line with unconsumed quantity
        $mo->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $component->id,
            'quantity_required' => 20.0, // 2 components per product * 10 products
            'quantity_consumed' => 0.0,
            'unit_cost' => 5000,
            'currency_code' => $this->company->currency->code,
        ]);

        // Act
        $action = app(ConsumeComponentsAction::class);
        $updatedMo = $action->execute($mo);

        // Assert - lines should be marked as consumed
        $updatedMo->load('lines');
        $line = $updatedMo->lines->first();
        expect((float) $line->quantity_consumed)->toBe(20.0);
    });

    it('throws exception when consuming components for draft order', function () {
        // Arrange
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $bom->product_id,
            'status' => ManufacturingOrderStatus::Draft,
            'source_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
        ]);

        // Act & Assert
        $action = app(ConsumeComponentsAction::class);

        expect(fn () => $action->execute($mo))
            ->toThrow(InvalidArgumentException::class, 'Only in-progress manufacturing orders can consume components.');
    });

    it('throws exception when consuming components for confirmed order', function () {
        // Arrange
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $bom->product_id,
            'status' => ManufacturingOrderStatus::Confirmed, // Not yet started
            'source_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
        ]);

        // Act & Assert
        $action = app(ConsumeComponentsAction::class);

        expect(fn () => $action->execute($mo))
            ->toThrow(InvalidArgumentException::class);
    });

    it('throws exception when consuming components for done order', function () {
        // Arrange
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $bom->product_id,
            'status' => ManufacturingOrderStatus::Done,
            'source_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
        ]);

        // Act & Assert
        $action = app(ConsumeComponentsAction::class);

        expect(fn () => $action->execute($mo))
            ->toThrow(InvalidArgumentException::class);
    });

    it('only consumes unconsumed quantity', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
        ]);

        $component = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
        ]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $finishedProduct->id,
            'status' => ManufacturingOrderStatus::InProgress,
            'source_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
        ]);

        // Line already partially consumed
        $mo->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $component->id,
            'quantity_required' => 100.0,
            'quantity_consumed' => 40.0, // Already consumed 40
            'unit_cost' => 1000,
            'currency_code' => $this->company->currency->code,
        ]);

        // Act
        $action = app(ConsumeComponentsAction::class);
        $updatedMo = $action->execute($mo);

        // Assert - should mark as fully consumed
        $updatedMo->load('lines');
        $line = $updatedMo->lines->first();
        expect((float) $line->quantity_consumed)->toBe(100.0);
    });

    it('handles multiple component lines', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
        ]);

        $component1 = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Component 1'],
            'type' => ProductType::Storable,
        ]);

        $component2 = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Component 2'],
            'type' => ProductType::Storable,
        ]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $finishedProduct->id,
            'status' => ManufacturingOrderStatus::InProgress,
            'source_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
        ]);

        // Create multiple lines
        $mo->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $component1->id,
            'quantity_required' => 5.0,
            'quantity_consumed' => 0.0,
            'unit_cost' => 10000,
            'currency_code' => $this->company->currency->code,
        ]);

        $mo->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $component2->id,
            'quantity_required' => 10.0,
            'quantity_consumed' => 0.0,
            'unit_cost' => 5000,
            'currency_code' => $this->company->currency->code,
        ]);

        // Act
        $action = app(ConsumeComponentsAction::class);
        $updatedMo = $action->execute($mo);

        // Assert
        $updatedMo->load('lines');
        expect($updatedMo->lines)->toHaveCount(2);

        $line1 = $updatedMo->lines->where('product_id', $component1->id)->first();
        expect((float) $line1->quantity_consumed)->toBe(5.0);

        $line2 = $updatedMo->lines->where('product_id', $component2->id)->first();
        expect((float) $line2->quantity_consumed)->toBe(10.0);
    });

    it('skips lines that are already fully consumed', function () {
        // Arrange
        $finishedProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
        ]);

        $component = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
        ]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $finishedProduct->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $finishedProduct->id,
            'status' => ManufacturingOrderStatus::InProgress,
            'source_location_id' => $this->sourceLocation->id,
            'destination_location_id' => $this->destinationLocation->id,
        ]);

        // Already fully consumed
        $mo->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $component->id,
            'quantity_required' => 50.0,
            'quantity_consumed' => 50.0, // Already fully consumed
            'unit_cost' => 2000,
            'currency_code' => $this->company->currency->code,
        ]);

        // Act - should not throw or create stock move
        $action = app(ConsumeComponentsAction::class);
        $updatedMo = $action->execute($mo);

        // Assert
        $updatedMo->load('lines');
        $line = $updatedMo->lines->first();
        expect((float) $line->quantity_consumed)->toBe(50.0);
    });
});

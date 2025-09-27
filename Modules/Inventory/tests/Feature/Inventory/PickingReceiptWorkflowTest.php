<?php

namespace Modules\Inventory\Tests\Feature\Inventory;


use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Enums\Products\ProductType;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    $this->product = Product::factory()->for($this->company)->create([
        'type' => ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);
});

it('creates a receipt picking and updates quants when posting a vendor bill', function () {
    $qty = 8;
    $unitCost = Money::of(100, $this->company->currency->code);

    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => 'draft',
        'bill_date' => now()->toDateString(),
    ]);

    // Add a storable product line to the bill
    $lineDto = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Test Product',
        quantity: $qty,
        unit_price: (string) $unitCost->getAmount(),
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

    // Post the vendor bill
    app(VendorBillService::class)->post($vendorBill->fresh(), $this->user);

    // Assert picking created
    $pickings = StockPicking::where('company_id', $this->company->id)->get();
    expect($pickings->count())->toBe(1);

    $picking = $pickings->first();
    expect($picking->type)->toBe(StockPickingType::Receipt);
    expect($picking->state)->toBe(StockPickingState::Done);

    // The stock move created for this bill must belong to the picking
    $move = $picking->stockMoves()->first();
    expect($move)->not->toBeNull();

    // Check product line for product
    $productLine = $move->productLines()->where('product_id', $this->product->id)->first();
    expect($productLine)->not->toBeNull();

    // Quant updated at company's default stock location
    $quant = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->company->default_stock_location_id)
        ->first();

    expect($quant)->not->toBeNull();
    expect($quant->quantity)->toBe((float) $qty);
    expect($quant->reserved_quantity)->toBe(0.0);
});

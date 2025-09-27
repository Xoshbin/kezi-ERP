<?php

namespace Modules\Inventory\Tests\Feature\Inventory;

use App\Actions\Purchases\CreateVendorBillLineAction;
use App\Actions\Sales\CreateInvoiceLineAction;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Enums\Inventory\StockPickingState;
use App\Enums\Inventory\StockPickingType;
use App\Enums\Products\ProductType;
use App\Enums\Sales\InvoiceStatus;
use App\Models\StockPicking;
use App\Models\StockQuant;
use App\Services\InvoiceService;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Ensure company uses AUTO_RECORD_ON_BILL mode for these tests
    $this->company->update([
        'inventory_accounting_mode' => \App\Enums\Inventory\InventoryAccountingMode::AUTO_RECORD_ON_BILL,
    ]);

    $this->product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => \App\Enums\Inventory\ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    // Ensure product has a COGS account for outgoing valuation
    $this->cogsAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'type' => 'cost_of_revenue',
    ]);
    $this->product->update(['default_cogs_account_id' => $this->cogsAccount->id]);
});

it('creates a delivery picking for posted invoice and reserves then consumes available stock', function () {
    // Seed incoming qty: post a vendor bill for 5 units
    $qtyIn = 5;
    $cost = Money::of(100, $this->company->currency->code);

    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => 'draft',
    ]);
    $billLine = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Restock',
        quantity: $qtyIn,
        unit_price: (string) $cost->getAmount(),
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $billLine);
    app(VendorBillService::class)->post($vendorBill->fresh(), $this->user);

    // Sanity: quant should be increased at warehouse
    $quantBefore = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->company->default_stock_location_id)
        ->first();
    expect($quantBefore)->not->toBeNull();
    expect($quantBefore->quantity)->toBe((float) $qtyIn);

    // Create and post an invoice for 3 units
    $qtyOut = 3;
    $customer = \Modules\Foundation\Models\Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
    ]);
    $invoice = \Modules\Sales\Models\Invoice::factory()->for($this->company)->create([
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
    ]);
    $lineDto = new CreateInvoiceLineDTO(
        product_id: $this->product->id,
        description: 'Sale',
        quantity: $qtyOut,
        unit_price: Money::of(200, $this->company->currency->code),
        income_account_id: $this->product->income_account_id,
        tax_id: null,
    );
    app(CreateInvoiceLineAction::class)->execute($invoice, $lineDto);
    app(InvoiceService::class)->confirm($invoice->fresh(), $this->user);

    // Assert Delivery picking exists and is Done
    $deliveryPickings = StockPicking::where('company_id', $this->company->id)
        ->where('type', StockPickingType::Delivery)
        ->get();
    expect($deliveryPickings->count())->toBe(1);
    $picking = $deliveryPickings->first();
    expect($picking->state)->toBe(StockPickingState::Done);

    // Assert move attached to picking
    $move = $picking->stockMoves()->first();
    expect($move)->not->toBeNull();

    // Check product line for product
    $productLine = $move->productLines()->where('product_id', $this->product->id)->first();
    expect($productLine)->not->toBeNull();

    // Quant should now be decreased by 3, reserved back to 0
    $quantAfter = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->company->default_stock_location_id)
        ->first();
    expect($quantAfter->quantity)->toBe((float) ($qtyIn - $qtyOut));
    expect($quantAfter->reserved_quantity)->toBe(0.0);
});

it('partially reserves when oversold and consumes only reserved amount', function () {
    // We currently have remaining qty from previous test run due to RefreshDatabase isolation -- ensure fresh state
    $qtyIn = 5;
    $cost = Money::of(100, $this->company->currency->code);

    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => 'draft',
    ]);
    $billLine = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Restock',
        quantity: $qtyIn,
        unit_price: (string) $cost->getAmount(),
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null,
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $billLine);
    app(VendorBillService::class)->post($vendorBill->fresh(), $this->user);

    // Oversell: try to sell 10
    $qtyOut = 10;
    $customer = \Modules\Foundation\Models\Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
    ]);
    $invoice = \Modules\Sales\Models\Invoice::factory()->for($this->company)->create([
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
    ]);
    $lineDto = new CreateInvoiceLineDTO(
        product_id: $this->product->id,
        description: 'Big Sale',
        quantity: $qtyOut,
        unit_price: Money::of(200, $this->company->currency->code),
        income_account_id: $this->product->income_account_id,
        tax_id: null,
    );
    app(CreateInvoiceLineAction::class)->execute($invoice, $lineDto);
    app(InvoiceService::class)->confirm($invoice->fresh(), $this->user);

    // Quant should have only consumed 5 (all available), and be zero now
    $quantAfter = StockQuant::where('company_id', $this->company->id)
        ->where('product_id', $this->product->id)
        ->where('location_id', $this->company->default_stock_location_id)
        ->first();
    expect($quantAfter->quantity)->toBe(0.0);
    expect($quantAfter->reserved_quantity)->toBe(0.0);
});

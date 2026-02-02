<?php

namespace Kezi\Purchase\Tests\Feature\Purchases;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\CreateVendorBillFromPurchaseOrderAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillFromPurchaseOrderDTO;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Use the stock input account already set up by setupInventoryTestEnvironment
    // $this->stockInputAccount is already available and set as default on company

    // Create a Storable Product WITHOUT expense account
    $this->storableProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
        'expense_account_id' => null,
        'default_stock_input_account_id' => null, // FORCE NULL to test fallback
        'name' => 'Laptop Pro',
    ]);

    // Create a Consumable Product WITHOUT expense account (Should still fail or maybe use fallback if valid)
    // For now focusing on Storable success
});

it('uses company default stock input account for storable products if product expense account is missing', function () {
    // 1. Create PO
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::ToBill,
        'po_number' => 'PO-ST-001',
    ]);

    $purchaseOrder->lines()->create([
        'product_id' => $this->storableProduct->id,
        'description' => 'Laptop Pro',
        'quantity' => 5.0,
        'unit_price' => Money::of(1000, $this->company->currency->code),
        'subtotal' => Money::of(5000, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(5000, $this->company->currency->code),
    ]);

    // 2. Attempt to create Bill
    $dto = new CreateVendorBillFromPurchaseOrderDTO(
        purchase_order_id: $purchaseOrder->id,
        bill_reference: 'BILL-ST-001',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        created_by_user_id: $this->user->id,
        payment_term_id: null,
        copy_all_lines: true,
        line_quantities: []
    );

    // Verify company has default stock input account set
    $this->company->refresh();
    expect($this->company->default_stock_input_account_id)->not->toBeNull();

    $vendorBill = app(CreateVendorBillFromPurchaseOrderAction::class)->execute($dto);

    // 3. Assertions
    expect($vendorBill)->toBeInstanceOf(VendorBill::class);

    $line = $vendorBill->lines->first();
    expect($line->product_id)->toBe($this->storableProduct->id);
    // CRITICAL ASSERTION: Should use the company default stock input account
    expect($line->expense_account_id)->toBe($this->stockInputAccount->id);
});

<?php

namespace Modules\Purchase\Tests\Feature\Purchases;

use Brick\Money\Money;
use Modules\Product\Models\Product;
use Modules\Accounting\Models\Account;
use Modules\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Validation\ValidationException;
use Modules\Product\Enums\Products\ProductType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Actions\Purchases\CreateVendorBillFromPurchaseOrderAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillFromPurchaseOrderDTO;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Create an expense account for products
    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'expense',
        'code' => '5000',
        'name' => 'Cost of Goods Sold',
    ]);

    // Create a product with expense account
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Service,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    // Create a purchase order that can create bills
    $this->purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::ToBill, // Status that allows bill creation
        'confirmed_at' => now(),
        'po_number' => 'PO-2024-001',
    ]);

    // Add lines to the purchase order
    $this->purchaseOrder->lines()->create([
        'product_id' => $this->product->id,
        'description' => 'Test Product Line 1',
        'quantity' => 10.0,
        'unit_price' => Money::of(1000, $this->company->currency->code),
        'subtotal' => Money::of(10000, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(10000, $this->company->currency->code),
    ]);

    $this->purchaseOrder->lines()->create([
        'product_id' => $this->product->id,
        'description' => 'Test Product Line 2',
        'quantity' => 5.0,
        'unit_price' => Money::of(2000, $this->company->currency->code),
        'subtotal' => Money::of(10000, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(10000, $this->company->currency->code),
    ]);
});

it('can create a vendor bill from a purchase order', function () {
    $dto = new CreateVendorBillFromPurchaseOrderDTO(
        purchase_order_id: $this->purchaseOrder->id,
        bill_reference: 'VENDOR-BILL-001',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        created_by_user_id: $this->user->id,
        payment_term_id: null,
        copy_all_lines: true,
        line_quantities: []
    );

    $vendorBill = app(CreateVendorBillFromPurchaseOrderAction::class)->execute($dto);

    expect($vendorBill)->toBeInstanceOf(VendorBill::class);
    expect($vendorBill->purchase_order_id)->toBe($this->purchaseOrder->id);
    expect($vendorBill->vendor_id)->toBe($this->purchaseOrder->vendor_id);
    expect($vendorBill->currency_id)->toBe($this->purchaseOrder->currency_id);
    expect($vendorBill->bill_reference)->toBe('VENDOR-BILL-001');
    expect($vendorBill->status)->toBe(VendorBillStatus::Draft);
    expect($vendorBill->lines)->toHaveCount(2);

    // Check that lines are properly copied
    $firstLine = $vendorBill->lines->first();
    expect($firstLine->product_id)->toBe($this->product->id);
    expect($firstLine->description)->toBe('Test Product Line 1');
    expect((float) $firstLine->quantity)->toBe(10.0);
    expect($firstLine->unit_price)->toEqual(Money::of(1000, $this->company->currency->code));
    expect($firstLine->expense_account_id)->toBe($this->expenseAccount->id);
});

it('can create a vendor bill with custom quantities', function () {
    $dto = new CreateVendorBillFromPurchaseOrderDTO(
        purchase_order_id: $this->purchaseOrder->id,
        bill_reference: 'VENDOR-BILL-002',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        created_by_user_id: $this->user->id,
        payment_term_id: null,
        copy_all_lines: false,
        line_quantities: [
            $this->purchaseOrder->lines->first()->id => 5.0, // Half quantity
            $this->purchaseOrder->lines->last()->id => 3.0,  // Partial quantity
        ]
    );

    $vendorBill = app(CreateVendorBillFromPurchaseOrderAction::class)->execute($dto);

    expect($vendorBill->lines)->toHaveCount(2);

    $firstLine = $vendorBill->lines->first();
    expect((float) $firstLine->quantity)->toBe(5.0); // Custom quantity

    $secondLine = $vendorBill->lines->last();
    expect((float) $secondLine->quantity)->toBe(3.0); // Custom quantity
});

it('validates that purchase order exists', function () {
    $dto = new CreateVendorBillFromPurchaseOrderDTO(
        purchase_order_id: 99999, // Non-existent PO
        bill_reference: 'VENDOR-BILL-003',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        created_by_user_id: $this->user->id,
        payment_term_id: null,
        copy_all_lines: true,
        line_quantities: []
    );

    expect(fn() => app(CreateVendorBillFromPurchaseOrderAction::class)->execute($dto))
        ->toThrow(ValidationException::class);
});

it('validates that purchase order can create bills', function () {
    // Create a draft PO that cannot create bills
    $draftPO = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Draft, // Cannot create bills
    ]);

    $dto = new CreateVendorBillFromPurchaseOrderDTO(
        purchase_order_id: $draftPO->id,
        bill_reference: 'VENDOR-BILL-004',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        created_by_user_id: $this->user->id,
        payment_term_id: null,
        copy_all_lines: true,
        line_quantities: []
    );

    expect(fn() => app(CreateVendorBillFromPurchaseOrderAction::class)->execute($dto))
        ->toThrow(ValidationException::class);
});

it('validates that products have expense accounts', function () {
    // Create a product without expense account
    $productWithoutAccount = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Service,
        'expense_account_id' => null, // No expense account
    ]);

    // Create PO with this product
    $poWithBadProduct = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->company->currency->id,
        'created_by_user_id' => $this->user->id,
        'status' => PurchaseOrderStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $poWithBadProduct->lines()->create([
        'product_id' => $productWithoutAccount->id,
        'description' => 'Product without expense account',
        'quantity' => 1.0,
        'unit_price' => Money::of(1000, $this->company->currency->code),
        'subtotal' => Money::of(1000, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'total' => Money::of(1000, $this->company->currency->code),
    ]);

    $dto = new CreateVendorBillFromPurchaseOrderDTO(
        purchase_order_id: $poWithBadProduct->id,
        bill_reference: 'VENDOR-BILL-005',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        created_by_user_id: $this->user->id,
        payment_term_id: null,
        copy_all_lines: true,
        line_quantities: []
    );

    expect(fn() => app(CreateVendorBillFromPurchaseOrderAction::class)->execute($dto))
        ->toThrow(ValidationException::class);
});

it('establishes bidirectional relationship between PO and vendor bill', function () {
    $dto = new CreateVendorBillFromPurchaseOrderDTO(
        purchase_order_id: $this->purchaseOrder->id,
        bill_reference: 'VENDOR-BILL-006',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        created_by_user_id: $this->user->id,
        payment_term_id: null,
        copy_all_lines: true,
        line_quantities: []
    );

    $vendorBill = app(CreateVendorBillFromPurchaseOrderAction::class)->execute($dto);

    // Test forward relationship
    expect($vendorBill->purchaseOrder)->not->toBeNull();
    expect($vendorBill->purchaseOrder->id)->toBe($this->purchaseOrder->id);

    // Test reverse relationship
    $this->purchaseOrder->refresh();
    expect($this->purchaseOrder->vendorBills)->toHaveCount(1);
    expect($this->purchaseOrder->vendorBills->first()->id)->toBe($vendorBill->id);
});

it('can handle multiple vendor bills from same purchase order', function () {
    // Create first bill
    $dto1 = new CreateVendorBillFromPurchaseOrderDTO(
        purchase_order_id: $this->purchaseOrder->id,
        bill_reference: 'VENDOR-BILL-007A',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        created_by_user_id: $this->user->id,
        payment_term_id: null,
        copy_all_lines: false,
        line_quantities: [
            $this->purchaseOrder->lines->first()->id => 5.0, // Partial
        ]
    );

    $vendorBill1 = app(CreateVendorBillFromPurchaseOrderAction::class)->execute($dto1);

    // Create second bill
    $dto2 = new CreateVendorBillFromPurchaseOrderDTO(
        purchase_order_id: $this->purchaseOrder->id,
        bill_reference: 'VENDOR-BILL-007B',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        created_by_user_id: $this->user->id,
        payment_term_id: null,
        copy_all_lines: false,
        line_quantities: [
            $this->purchaseOrder->lines->first()->id => 5.0, // Remaining
            $this->purchaseOrder->lines->last()->id => 5.0,  // Full
        ]
    );

    $vendorBill2 = app(CreateVendorBillFromPurchaseOrderAction::class)->execute($dto2);

    // Both bills should be linked to the same PO
    expect($vendorBill1->purchase_order_id)->toBe($this->purchaseOrder->id);
    expect($vendorBill2->purchase_order_id)->toBe($this->purchaseOrder->id);

    // PO should have both bills
    $this->purchaseOrder->refresh();
    expect($this->purchaseOrder->vendorBills)->toHaveCount(2);
});

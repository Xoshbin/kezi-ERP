<?php

namespace Jmeryar\Purchase\Tests\Feature\Actions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Jmeryar\Accounting\Enums\Accounting\TaxType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\Tax;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Enums\Products\ProductType;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Actions\Purchases\CreateVendorBillFromPurchaseOrderAction;
use Jmeryar\Purchase\DataTransferObjects\Purchases\CreateVendorBillFromPurchaseOrderDTO;
use Jmeryar\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Jmeryar\Purchase\Enums\Purchases\VendorBillStatus;
use Jmeryar\Purchase\Models\PurchaseOrder;
use Jmeryar\Purchase\Models\PurchaseOrderLine;
use Jmeryar\Purchase\Models\VendorBill;
use Jmeryar\Purchase\Services\VendorBillService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateVendorBillFromPurchaseOrderAction::class);

    // Setup accounts
    $this->stockInputAccount = Account::factory()->for($this->company)->create([
        'name' => 'Stock Interim (Received)',
        'type' => 'current_liabilities',
    ]);

    $this->expenseAccount = Account::factory()->for($this->company)->create([
        'name' => 'Expense Account',
        'type' => 'expense',
    ]);

    $this->company->update([
        'default_stock_input_account_id' => $this->stockInputAccount->id,
        'default_accounts_payable_id' => Account::factory()->for($this->company)->create(['type' => 'payable'])->id,
        'default_purchase_journal_id' => Journal::factory()->for($this->company)->create(['type' => 'purchase'])->id,
        'default_tax_receivable_id' => Account::factory()->for($this->company)->create(['type' => 'current_assets'])->id,
    ]);

    // Setup vendor
    $this->vendor = Partner::factory()->for($this->company)->create([
        'type' => \Jmeryar\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    // Setup products
    $this->storableProduct = Product::factory()->for($this->company)->create([
        'name' => 'Storable Product',
        'type' => ProductType::Storable,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $this->serviceProduct = Product::factory()->for($this->company)->create([
        'name' => 'Service Product',
        'type' => ProductType::Service,
        'expense_account_id' => $this->expenseAccount->id,
    ]);
});

describe('CreateVendorBillFromPurchaseOrderAction', function () {
    it('successfully creates a vendor bill from a confirmed purchase order', function () {
        $po = PurchaseOrder::factory()->for($this->company)->create([
            'vendor_id' => $this->vendor->id,
            'status' => PurchaseOrderStatus::Confirmed,
            'currency_id' => $this->company->currency_id,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->storableProduct->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->serviceProduct->id,
            'quantity' => 2,
            'unit_price' => 50,
        ]);

        $po->refresh()->calculateTotalsFromLines();
        $po->save();

        $dto = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'BILL-001',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: now()->addDays(30)->format('Y-m-d'),
            created_by_user_id: $this->user->id
        );

        $vendorBill = $this->action->execute($dto);

        expect($vendorBill)->toBeInstanceOf(VendorBill::class)
            ->purchase_order_id->toBe($po->id)
            ->vendor_id->toBe($po->vendor_id)
            ->currency_id->toBe($po->currency_id)
            ->status->toBe(VendorBillStatus::Draft)
            ->bill_reference->toBe('BILL-001')
            ->lines->toHaveCount(2);

        expect($vendorBill->total_amount->getAmount()->toFloat())->toBe(1100.0);

        $po->refresh();
        expect($po->status)->toBe(PurchaseOrderStatus::PartiallyBilled);
        expect($po->vendor_id)->toBe($vendorBill->vendor_id);
    });

    it('transfers taxes correctly from PO lines to vendor bill lines', function () {
        $taxAccount = Account::factory()->for($this->company)->create(['type' => 'current_assets']);
        $tax = Tax::factory()->for($this->company)->create([
            'rate' => 15.0, // 15%
            'tax_account_id' => $taxAccount->id,
            'type' => TaxType::Purchase,
        ]);

        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->serviceProduct->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_id' => $tax->id,
        ]);
        $line->calculateTotals();
        $line->save();

        $po->refresh()->calculateTotalsFromLines();
        $po->save();

        expect($po->total_amount->getAmount()->toFloat())->toBe(1150.0);
        expect($po->total_tax->getAmount()->toFloat())->toBe(150.0);

        $dto = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'TAX-BILL',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id
        );

        $vendorBill = $this->action->execute($dto);
        $billLine = $vendorBill->lines->first();

        expect($billLine->tax_id)->toBe($tax->id);
        expect($billLine->total_line_tax->getAmount()->toFloat())->toBe(150.0);
        expect($vendorBill->total_amount->getAmount()->toFloat())->toBe(1150.0);
    });

    it('creates proper accounting entries when the resulting vendor bill is posted', function () {
        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->serviceProduct->id,
            'quantity' => 1,
            'unit_price' => 500,
        ]);

        $dto = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'POST-ME',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id
        );

        $vendorBill = $this->action->execute($dto);

        // Ensure user has permission by giving it directly to skip seeder issues
        $this->user->givePermissionTo('confirm_vendor_bill');

        $service = app(VendorBillService::class);
        $service->confirm($vendorBill, $this->user);

        $vendorBill->refresh();
        expect($vendorBill->status)->toBe(VendorBillStatus::Posted);
        expect($vendorBill->journal_entry_id)->not->toBeNull();

        $entry = $vendorBill->journalEntry;
        // Verify accounting lines
        $debitLine = $entry->lines->filter(fn ($l) => $l->debit->isPositive())->first();
        $creditLine = $entry->lines->filter(fn ($l) => $l->credit->isPositive())->first();

        expect($debitLine)->not->toBeNull();
        expect($creditLine)->not->toBeNull();

        expect($debitLine->account_id)->toBe($this->expenseAccount->id);
        expect($debitLine->debit->getAmount()->toFloat())->toBe(500.0);

        expect($creditLine->account_id)->toBe($this->company->default_accounts_payable_id);
        expect($creditLine->credit->getAmount()->toFloat())->toBe(500.0);
    });

    it('copies line items from PO to vendor bill with correct amounts and accounts', function () {
        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->storableProduct->id,
            'quantity' => 5,
            'unit_price' => 150,
            'description' => 'Custom Description',
        ]);

        $dto = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'REF-123',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id
        );

        $vendorBill = $this->action->execute($dto);
        $billLine = $vendorBill->lines->first();

        expect($billLine)
            ->product_id->toBe($line->product_id)
            ->quantity->toEqual('5.00')
            ->unit_price->getAmount()->toFloat()->toBe(150.0)
            ->description->toBe('Custom Description')
            ->expense_account_id->toBe($this->stockInputAccount->id);
    });

    it('handles multi-currency purchase orders', function () {
        $usd = Currency::factory()->create(['code' => 'USD']);

        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Confirmed,
            'currency_id' => $usd->id,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->storableProduct->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $dto = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'USD-BILL',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id
        );

        $vendorBill = $this->action->execute($dto);

        expect($vendorBill->currency_id)->toBe($usd->id);
        expect($vendorBill->total_amount->getCurrency()->getCurrencyCode())->toBe('USD');
    });

    it('validates that only confirmed POs can generate bills', function () {
        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Draft,
        ]);

        $dto = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'BILL-FAIL',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id
        );

        expect(fn () => $this->action->execute($dto))
            ->toThrow(ValidationException::class, 'The purchase order status does not allow creating bills.');
    });

    it('handles partial billing scenarios via line_quantities', function () {
        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        $line1 = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->storableProduct->id,
            'quantity' => 10,
        ]);

        $line2 = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->serviceProduct->id,
            'quantity' => 5,
        ]);

        $dto = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'PARTIAL',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id,
            line_quantities: [
                $line1->id => 4,
                $line2->id => 2,
            ]
        );

        $vendorBill = $this->action->execute($dto);

        expect($vendorBill->lines)->toHaveCount(2);
        expect((float) $vendorBill->lines->where('product_id', $this->storableProduct->id)->first()->quantity)->toBe(4.0);
        expect((float) $vendorBill->lines->where('product_id', $this->serviceProduct->id)->first()->quantity)->toBe(2.0);
    });

    it('skips lines with zero quantity', function () {
        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        $line1 = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->storableProduct->id,
            'quantity' => 10,
        ]);

        $line2 = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->serviceProduct->id,
            'quantity' => 5,
        ]);

        $dto = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'SKIP-ZERO',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id,
            line_quantities: [
                $line1->id => 10,
                $line2->id => 0,
            ]
        );

        $vendorBill = $this->action->execute($dto);

        expect($vendorBill->lines)->toHaveCount(1);
        expect($vendorBill->lines->first()->product_id)->toBe($this->storableProduct->id);
    });

    it('fails if no valid lines reach the bill', function () {
        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->storableProduct->id,
            'quantity' => 10,
        ]);

        $dto = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'TOTAL-SKIP',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id,
            line_quantities: [
                $line->id => 0,
            ]
        );

        expect(fn () => $this->action->execute($dto))
            ->toThrow(ValidationException::class, 'No valid lines found to create vendor bill.');
    });

    it('fails if product is missing account configuration', function () {
        $brokenProduct = Product::factory()->for($this->company)->create([
            'name' => 'Broken Product',
            'type' => ProductType::Service,
            'expense_account_id' => null,
            'income_account_id' => null,
        ]);

        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $brokenProduct->id,
            'quantity' => 1,
            'description' => 'Broken Product',
        ]);

        $dto = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'NO-ACCOUNT',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id
        );

        expect(fn () => $this->action->execute($dto))
            ->toThrow(ValidationException::class, "Product 'Broken Product' must have a Expense Account configured.");
    });

    it('correctly determined expense account for storable product via company fallback', function () {
        $storableWithoutAccount = Product::factory()->for($this->company)->create([
            'type' => ProductType::Storable,
            'default_stock_input_account_id' => null,
        ]);

        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $storableWithoutAccount->id,
            'quantity' => 1,
        ]);

        $dto = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'FALLBACK',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id
        );

        $vendorBill = $this->action->execute($dto);
        expect($vendorBill->lines->first()->expense_account_id)->toBe($this->stockInputAccount->id);
    });

    it('allows creating multiple bills if status is PartiallyBilled', function () {
        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        $poLine = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->storableProduct->id,
            'quantity' => 10,
        ]);

        $dto1 = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'BILL-1',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id,
            line_quantities: [$poLine->id => 5]
        );

        $this->action->execute($dto1);
        $po->refresh();
        expect($po->status)->toBe(PurchaseOrderStatus::PartiallyBilled);

        // Try second bill
        $dto2 = new CreateVendorBillFromPurchaseOrderDTO(
            purchase_order_id: $po->id,
            bill_reference: 'BILL-2',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            created_by_user_id: $this->user->id,
            line_quantities: [$poLine->id => 5]
        );

        $vendorBill2 = $this->action->execute($dto2);
        expect($vendorBill2)->toBeInstanceOf(VendorBill::class);
        expect($po->vendorBills()->count())->toBe(2);
    });
});

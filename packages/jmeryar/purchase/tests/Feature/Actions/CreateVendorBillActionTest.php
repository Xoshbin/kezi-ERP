<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Actions\Purchases\CreateVendorBillAction;
use Jmeryar\Purchase\DataTransferObjects\Purchases\CreateVendorBillDTO;
use Jmeryar\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Jmeryar\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Jmeryar\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Jmeryar\Purchase\Models\PurchaseOrder;
use Jmeryar\Purchase\Models\RequestForQuotation;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateVendorBillAction::class);
});

it('prevents double conversion', function () {
    $po = \Jmeryar\Purchase\Models\PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'status' => RequestForQuotationStatus::BidReceived,
        'converted_to_purchase_order_id' => $po->id,
    ]);

    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $expenseAccount = \Jmeryar\Accounting\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Expense,
    ]);

    $lineDto = new CreateVendorBillLineDTO(
        product_id: $product->id,
        description: 'Test Service',
        quantity: 1,
        unit_price: Money::of(500, $currency->code),
        expense_account_id: $expenseAccount->id,
        tax_id: null,
        analytic_account_id: null
    );

    $dto = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $po->vendor_id, // Use PO's vendor_id
        currency_id: $currency->id,
        bill_reference: 'BILL-001',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [$lineDto],
        created_by_user_id: $this->user->id,
        purchase_order_id: $po->id
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(ValidationException::class);
});

it('creates a vendor bill with lines and calculates totals', function () {
    $vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    // Ensure vendor has a fiscal position for auto-assignment test
    $fiscalPosition = \Jmeryar\Accounting\Models\FiscalPosition::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $vendor->update(['fiscal_position_id' => $fiscalPosition->id]);

    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $expenseAccount = \Jmeryar\Accounting\Models\Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Expense,
    ]);

    $lineDto = new CreateVendorBillLineDTO(
        product_id: $product->id,
        description: 'Test Service',
        quantity: 1,
        unit_price: Money::of(500, $currency->code),
        expense_account_id: $expenseAccount->id,
        tax_id: null,
        analytic_account_id: null
    );

    $dto = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $vendor->id,
        currency_id: $currency->id,
        bill_reference: 'BILL-001',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [$lineDto],
        created_by_user_id: $this->user->id
    );

    $bill = $this->action->execute($dto);

    expect($bill)->not->toBeNull();
    expect($bill->bill_reference)->toBe('BILL-001');
    expect($bill->total_amount->getAmount()->toFloat())->toBe(500.0);
    expect($bill->lines)->toHaveCount(1);
    expect($bill->fiscal_position_id)->not->toBeNull(); // Auto-assigned from vendor
});

it('throws PeriodIsLockedException for locked periods', function () {
    $lockDate = now()->addDay();
    \Jmeryar\Accounting\Models\LockDate::factory()->create([
        'company_id' => $this->company->id,
        'lock_type' => \Jmeryar\Accounting\Enums\Accounting\LockDateType::HardLock,
        'locked_until' => $lockDate,
    ]);

    $vendor = Partner::factory()->create(['company_id' => $this->company->id]);
    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);

    $dto = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $vendor->id,
        currency_id: $currency->id,
        bill_reference: 'LOCK-TEST',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        lines: [],
        created_by_user_id: $this->user->id
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(\Jmeryar\Accounting\Exceptions\PeriodIsLockedException::class);
});

it('validates purchase order compatibility', function () {
    $vendor = Partner::factory()->create(['company_id' => $this->company->id]);
    $otherVendor = Partner::factory()->create(['company_id' => $this->company->id]);
    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);

    $po = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $currency->id,
        'status' => PurchaseOrderStatus::RFQ, // RFQ cannot be billed
    ]);

    $dto = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $otherVendor->id, // Wrong vendor
        currency_id: $currency->id,
        bill_reference: 'VALIDATION-TEST',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        lines: [],
        created_by_user_id: $this->user->id,
        purchase_order_id: $po->id
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(ValidationException::class);
});

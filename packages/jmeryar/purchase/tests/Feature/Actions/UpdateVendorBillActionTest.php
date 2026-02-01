<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Actions\Purchases\UpdateVendorBillAction;
use Jmeryar\Purchase\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use Jmeryar\Purchase\DataTransferObjects\Purchases\VendorBillLineDTO;
use Jmeryar\Purchase\Enums\Purchases\VendorBillStatus;
use Jmeryar\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(UpdateVendorBillAction::class);
});

it('updates a draft vendor bill and replaces lines', function () {
    $vendor = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Jmeryar\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $expenseAccount = Account::factory()->create(['company_id' => $this->company->id]);

    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $currency->id,
        'status' => VendorBillStatus::Draft,
        'bill_date' => now()->subDay(),
    ]);

    // Add initial line
    $bill->lines()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'description' => 'Old Bill Line',
        'quantity' => 1,
        'unit_price' => Money::of(10, 'USD'),
        'subtotal' => Money::of(10, 'USD'),
        'total_line_tax' => Money::of(0, 'USD'),
        'expense_account_id' => $expenseAccount->id,
    ]);

    $lineDto = new VendorBillLineDTO(
        product_id: $product->id,
        description: 'New Bill Line',
        quantity: 10,
        unit_price: Money::of(100, 'USD'),
        tax_id: null,
        expense_account_id: $expenseAccount->id,
        analytic_account_id: null
    );

    $dto = new UpdateVendorBillDTO(
        vendorBill: $bill,
        company_id: $this->company->id,
        vendor_id: $vendor->id,
        currency_id: $currency->id,
        bill_reference: 'BILL-REF-NEW',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [$lineDto],
        updated_by_user_id: $this->user->id
    );

    $updatedBill = $this->action->execute($dto);

    expect($updatedBill->bill_reference)->toBe('BILL-REF-NEW');
    expect($updatedBill->lines)->toHaveCount(1);
    expect($updatedBill->lines->first()->description)->toBe('New Bill Line');
    expect($updatedBill->total_amount->getAmount()->toFloat())->toBe(1000.0);

    $this->assertDatabaseHas('vendor_bills', [
        'id' => $bill->id,
        'bill_reference' => 'BILL-REF-NEW',
    ]);

    $this->assertDatabaseMissing('vendor_bill_lines', [
        'description' => 'Old Bill Line',
        'vendor_bill_id' => $bill->id,
    ]);
});

it('throws exception when vendor bill is not in draft status', function () {
    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'status' => VendorBillStatus::Posted,
    ]);

    $dto = new UpdateVendorBillDTO(
        vendorBill: $bill,
        company_id: $this->company->id,
        vendor_id: $bill->vendor_id,
        currency_id: $bill->currency_id,
        bill_reference: 'REF',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        lines: [],
        updated_by_user_id: $this->user->id
    );

    $this->action->execute($dto);
})->throws(\Jmeryar\Foundation\Exceptions\UpdateNotAllowedException::class);

it('enforces accounting lock date for vendor bills', function () {
    // Create HardLock lock date until tomorrow
    \Jmeryar\Accounting\Models\LockDate::create([
        'company_id' => $this->company->id,
        'lock_type' => \Jmeryar\Accounting\Enums\Accounting\LockDateType::HardLock->value,
        'locked_until' => now()->addDay(),
    ]);

    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'status' => VendorBillStatus::Draft,
    ]);

    $dto = new UpdateVendorBillDTO(
        vendorBill: $bill,
        company_id: $this->company->id,
        vendor_id: $bill->vendor_id,
        currency_id: $bill->currency_id,
        bill_reference: 'REF',
        bill_date: now()->format('Y-m-d'),
        accounting_date: now()->format('Y-m-d'),
        due_date: null,
        lines: [],
        updated_by_user_id: $this->user->id
    );

    $this->action->execute($dto);
})->throws(\Jmeryar\Accounting\Exceptions\PeriodIsLockedException::class);

<?php

namespace Kezi\Purchase\Tests\Feature\Actions;

use Brick\Money\Money;
use Kezi\Accounting\Models\Account;
use Kezi\Inventory\Enums\Adjustments\AdjustmentDocumentStatus;
use Kezi\Inventory\Enums\Adjustments\AdjustmentDocumentType;
use Kezi\Purchase\Actions\Purchases\CreateDebitNoteAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateDebitNoteDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateDebitNoteLineDTO;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

/**
 * @var \Tests\TestCase $this
 */
uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Accounts
    $this->expenseAccount = Account::factory()->for($this->company)->create(['name' => 'Expense Account']);
    $this->apAccount = Account::factory()->for($this->company)->create(['name' => 'Accounts Payable']);

    $this->company->update([
        'default_accounts_payable_id' => $this->apAccount->id,
    ]);

    // Ensure Currency is USD standard
    $this->company->currency->update(['code' => 'USD', 'symbol' => '$', 'precision' => 2]);
    $this->currency = $this->company->currency;

    // Vendor
    $this->vendor = \Kezi\Foundation\Models\Partner::factory()->for($this->company)->create(['type' => 'vendor']);

    // Create a Posted Vendor Bill
    $this->vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
        'status' => VendorBillStatus::Posted,
        'bill_date' => now()->toDateString(),
        'bill_reference' => 'BILL-001',
    ]);
});

it('can create a debit note for a vendor bill', function () {
    $date = now()->toDateString();

    $lineDto = new CreateDebitNoteLineDTO(
        description: 'Return of Goods',
        quantity: 1,
        unit_price: Money::of(100, 'USD'),
        account_id: $this->expenseAccount->id,
        product_id: null,
        tax_id: null,
    );

    $dto = new CreateDebitNoteDTO(
        company_id: $this->company->id,
        vendor_bill_id: $this->vendorBill->id,
        date: $date,
        reason: 'Defective Goods',
        lines: [$lineDto],
    );

    $action = app(CreateDebitNoteAction::class);
    $debitNote = $action->execute($dto);

    // Assert Database
    $this->assertDatabaseHas('adjustment_documents', [
        'id' => $debitNote->id,
        'type' => AdjustmentDocumentType::DebitNote->value,
        'company_id' => $this->company->id,
        'original_vendor_bill_id' => $this->vendorBill->id,
    ]);

    expect($debitNote->status->value)->toBe(AdjustmentDocumentStatus::Draft->value);

    expect($debitNote->lines)->toHaveCount(1)
        ->and($debitNote->subtotal->getAmount()->toInt())->toBe(100)
        ->and($debitNote->total_amount->getAmount()->toInt())->toBe(100);
});

it('validates debit note creation', function () {
    $otherCompany = \App\Models\Company::factory()->create();
    $dto = new CreateDebitNoteDTO(
        company_id: $otherCompany->id,
        vendor_bill_id: $this->vendorBill->id,
        date: now()->toDateString(),
        reason: 'Test',
        lines: []
    );

    expect(fn () => app(CreateDebitNoteAction::class)->execute($dto))
        ->toThrow(\Exception::class, 'Vendor bill does not belong to the requested company.');

    $draftBill = VendorBill::factory()->for($this->company)->create([
        'status' => VendorBillStatus::Draft,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->currency->id,
    ]);

    $dto2 = new CreateDebitNoteDTO(
        company_id: $this->company->id,
        vendor_bill_id: $draftBill->id,
        date: now()->toDateString(),
        reason: 'Test',
        lines: []
    );

    expect(fn () => app(CreateDebitNoteAction::class)->execute($dto2))
        ->toThrow(\Exception::class, 'Debit notes can only be created for posted/paid vendor bills.');
});

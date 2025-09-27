<?php

namespace Modules\Payment\Tests\Feature\FinancialTransactions;

use App\Actions\Purchases\CreateVendorBillAction;
use App\Actions\Purchases\CreateVendorBillLineAction;
use App\Actions\Purchases\UpdateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Purchases\VendorBillStatus;
use App\Enums\Shared\PaymentState;
use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\PeriodIsLockedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\PaymentDocumentLink;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Traits\MocksTime;
use Tests\Traits\WithConfiguredCompany;

// Import the Action
// Import the DTO

uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
});

test('a draft vendor bill can be confirmed, which posts it and dispatches an event', function () {
    Event::fake();
    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($this->company)->create(['status' => 'draft']);

    // Use the dedicated Action to create the line.
    $lineDto = new CreateVendorBillLineDTO(
        description: 'Test Service',
        quantity: 1,
        unit_price: '100.00',
        expense_account_id: \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'expense'])->id,
        product_id: null,
        tax_id: null,
        analytic_account_id: null
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

    app(VendorBillService::class)->post($vendorBill, $this->user);

    $vendorBill->refresh();
    expect($vendorBill->status)->toBe(VendorBillStatus::Posted);
    Event::assertDispatched(\Modules\Purchase\Events\VendorBillConfirmed::class, fn($event) => $event->vendorBill->id === $vendorBill->id);
});

test('confirming a vendor bill generates the correct journal entry', function () {
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => \App\Enums\Inventory\ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);
    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($this->company)->create(['status' => VendorBillStatus::Draft]);

    // Use the dedicated Action to create the line, ensuring calculations are run.
    $lineDto = new CreateVendorBillLineDTO(
        description: $product->name,
        quantity: 3,
        unit_price: Money::of(5000, $this->company->currency->code),
        expense_account_id: \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'expense'])->id,
        product_id: $product->id,
        tax_id: null,
        analytic_account_id: null
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

    // The service call remains the same
    app(VendorBillService::class)->post($vendorBill, $this->user);

    // Phase 1: Anglo-Saxon separation creates 2 entries (valuation + AP recognition)
    $this->assertDatabaseCount('journal_entries', 2);
    // Use the AP recognition journal entry attached to the bill
    $journalEntry = $vendorBill->refresh()->journalEntry;

    $expectedTotal = Money::of(15000, $this->company->currency->code);
    expect($journalEntry->journal_id)->toBe($this->company->default_purchase_journal_id)
        ->and($journalEntry->total_debit)->toEqual($expectedTotal)
        ->and($journalEntry->total_credit)->toEqual($expectedTotal);

    // Phase 1: Debit Stock Input on the bill JE; AP credited
    expect(optional($journalEntry->lines()->where('account_id', $this->stockInputAccount->id)->first())->debit)->toEqual($expectedTotal);
    expect(optional($journalEntry->lines()->where('account_id', $this->company->default_accounts_payable_id)->first())->credit)->toEqual($expectedTotal);
});

test('a posted vendor bill cannot be updated', function () {
    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($this->company)->create(['status' => 'posted']);
    $newVendor = \Modules\Foundation\Models\Partner::factory()->for($this->company)->create();

    $updateDto = new UpdateVendorBillDTO(
        vendorBill: $vendorBill,
        company_id: $this->company->id,
        vendor_id: $newVendor->id,
        currency_id: $vendorBill->currency_id,
        bill_reference: $vendorBill->bill_reference,
        bill_date: $vendorBill->bill_date->toDateString(),
        accounting_date: $vendorBill->accounting_date->toDateString(),
        due_date: $vendorBill->due_date?->toDateString(),
        lines: [],
        updated_by_user_id: $this->user->id
    );

    expect(fn() => app(UpdateVendorBillAction::class)->execute($updateDto))
        ->toThrow(\Modules\Foundation\Exceptions\UpdateNotAllowedException::class);
});

test('a posted vendor bill cannot be deleted', function () {
    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($this->company)->create(['status' => 'posted']);
    expect(fn() => app(VendorBillService::class)->delete($vendorBill))
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class);
});

test('a draft vendor bill can be deleted', function () {
    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($this->company)->create(['status' => 'draft']);
    app(VendorBillService::class)->delete($vendorBill);
    $this->assertModelMissing($vendorBill);
});

test('a vendor bill cannot be created in a locked period', function () {
    $this->travelToDate('2026-02-15');

    \Modules\Accounting\Models\LockDate::factory()->for($this->company)->create([
        'locked_until' => '2026-01-31',
        'lock_type' => 'everything_date',
    ]);

    $vendorBillDto = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: \Modules\Foundation\Models\Partner::factory()->for($this->company)->create()->id,
        currency_id: $this->company->currency_id,
        bill_date: '2026-01-10',
        accounting_date: '2026-01-10',
        due_date: '2026-03-10',
        bill_reference: 'SHOULD-FAIL',
        lines: [],
        created_by_user_id: $this->user->id
    );

    expect(fn() => app(CreateVendorBillAction::class)->execute($vendorBillDto))
        ->toThrow(\Modules\Accounting\Exceptions\PeriodIsLockedException::class);
});

test('it correctly computes its payment state via a many-to-many relationship', function (Money $paidAmount, Money $totalAmount, \Modules\Foundation\Enums\Shared\PaymentState $expectedState) {
    // Arrange: Create the bill and a payment.
    // Assuming $this->company->currency is available from a test setup trait.
    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->create([
        'total_amount' => $totalAmount,
        'currency_id' => $this->company->currency->id,
    ]);

    if (! $paidAmount->isZero()) {
        $payment = \Modules\Payment\Models\Payment::factory()->create([
            'currency_id' => $this->company->currency->id,
            'status' => PaymentStatus::Confirmed, // Only confirmed/reconciled payments count toward payment state
        ]);

        // Action: Create payment document link using proper method
        PaymentDocumentLink::factory()->create([
            'payment_id' => $payment->id,
            'vendor_bill_id' => $vendorBill->id,
            'amount_applied' => $paidAmount,
        ]);
    }

    // Assert: Refresh the model to ensure the computed attribute is re-evaluated.
    expect($vendorBill->refresh()->paymentState)->toBe($expectedState);
})->with([
    'not paid' => [Money::of(0, 'IQD'), Money::of(150000, 'IQD'), \Modules\Foundation\Enums\Shared\PaymentState::NotPaid],
    'partially paid' => [Money::of(75000, 'IQD'), Money::of(150000, 'IQD'), \Modules\Foundation\Enums\Shared\PaymentState::PartiallyPaid],
    'fully paid' => [Money::of(150000, 'IQD'), Money::of(150000, 'IQD'), \Modules\Foundation\Enums\Shared\PaymentState::Paid],
    'overpaid' => [Money::of(160000, 'IQD'), Money::of(150000, 'IQD'), \Modules\Foundation\Enums\Shared\PaymentState::Paid],
]);

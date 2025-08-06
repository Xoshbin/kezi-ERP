<?php

namespace Tests\Feature\FinancialTransactions;

use Brick\Money\Money;
use App\Models\Partner;
use App\Models\Product;
use App\Models\LockDate;
use App\Models\VendorBill;
use Tests\Traits\MocksTime;
use App\Models\JournalEntry;
use App\Models\StockLocation;
use App\Events\VendorBillConfirmed;
use App\Services\VendorBillService;
use Illuminate\Support\Facades\Event;
use Tests\Traits\WithConfiguredCompany;
use App\Enums\Inventory\StockLocationType;
use App\Exceptions\PeriodIsLockedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Exceptions\DeletionNotAllowedException;
use App\Actions\Purchases\CreateVendorBillAction;
use App\Actions\Purchases\UpdateVendorBillAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use App\Actions\Purchases\CreateVendorBillLineAction; // Import the Action
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO; // Import the DTO

uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

test('a draft vendor bill can be confirmed, which posts it and dispatches an event', function () {
    Event::fake();
    $vendorBill = VendorBill::factory()->for($this->company)->create(['status' => 'draft']);

    // Use the dedicated Action to create the line.
    $lineDto = new CreateVendorBillLineDTO(
        description: 'Test Service',
        quantity: 1,
        unit_price: '100.00',
        expense_account_id: $this->company->accounts()->where('type', 'Expense')->first()->id,
        product_id: null,
        tax_id: null,
        analytic_account_id: null
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

    app(VendorBillService::class)->confirm($vendorBill, $this->user);

    $vendorBill->refresh();
    expect($vendorBill->status)->toBe(VendorBill::STATUS_POSTED);
    Event::assertDispatched(VendorBillConfirmed::class, fn ($event) => $event->vendorBill->id === $vendorBill->id);
});

test('confirming a vendor bill generates the correct journal entry', function () {
    $vendorLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::VENDOR]);
    $stockLocation = StockLocation::factory()->for($this->company)->create(['type' => StockLocationType::INTERNAL]);
    $this->company->update([
        'vendor_location_id' => $vendorLocation->id,
        'default_stock_location_id' => $stockLocation->id,
    ]);
    $expenseAccount = $this->company->accounts()->where('type', 'Expense')->firstOrFail();
    $product = Product::factory()->for($this->company)->create(['expense_account_id' => $expenseAccount->id]);
    $vendorBill = VendorBill::factory()->for($this->company)->create(['status' => 'draft']);

    // **THE FIX**: Replace the direct ->create() call with our robust Action.
    $lineDto = new CreateVendorBillLineDTO(
        description: $product->name,
        quantity: 3,
        unit_price: '50.00', // DTOs accept clean string representations of money
        expense_account_id: $expenseAccount->id,
        product_id: $product->id,
        tax_id: null,
        analytic_account_id: null
    );
    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);

    // The service now receives a fully consistent and correct VendorBill.
    app(VendorBillService::class)->confirm($vendorBill, $this->user);

    $this->assertDatabaseCount('journal_entries', 1);
    $journalEntry = JournalEntry::first();

    $expectedTotal = Money::of(150, $this->company->currency->code);
    expect($journalEntry->journal_id)->toBe($this->company->default_purchase_journal_id)
        ->and($journalEntry->total_debit)->toEqual($expectedTotal)
        ->and($journalEntry->total_credit)->toEqual($expectedTotal);

    expect($journalEntry->lines()->where('account_id', $expenseAccount->id)->first()->debit)->toEqual($expectedTotal);
    expect($journalEntry->lines()->where('account_id', $this->company->default_accounts_payable_id)->first()->credit)->toEqual($expectedTotal);
});

test('a posted vendor bill cannot be updated', function () {
    $vendorBill = VendorBill::factory()->for($this->company)->create(['status' => 'posted']);
    $newVendor = Partner::factory()->for($this->company)->create();

    $updateDto = new UpdateVendorBillDTO(
        vendorBill: $vendorBill,
        vendor_id: $newVendor->id,
        currency_id: $vendorBill->currency_id,
        bill_reference: $vendorBill->bill_reference,
        bill_date: $vendorBill->bill_date->toDateString(),
        accounting_date: $vendorBill->accounting_date->toDateString(),
        due_date: $vendorBill->due_date?->toDateString(),
        lines: [],
    );

    expect(fn() => app(UpdateVendorBillAction::class)->execute($updateDto))
        ->toThrow(UpdateNotAllowedException::class);
});

test('a posted vendor bill cannot be deleted', function () {
    $vendorBill = VendorBill::factory()->for($this->company)->create(['status' => 'posted']);
    expect(fn() => app(VendorBillService::class)->delete($vendorBill))
        ->toThrow(DeletionNotAllowedException::class);
});

test('a draft vendor bill can be deleted', function () {
    $vendorBill = VendorBill::factory()->for($this->company)->create(['status' => 'draft']);
    app(VendorBillService::class)->delete($vendorBill);
    $this->assertModelMissing($vendorBill);
});

test('a vendor bill cannot be created in a locked period', function () {
    $this->travelToDate('2026-02-15');

    LockDate::factory()->for($this->company)->create([
        'locked_until' => '2026-01-31',
    ]);

    $vendorBillDto = new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: Partner::factory()->for($this->company)->create()->id,
        currency_id: $this->company->currency_id,
        bill_date: '2026-01-10',
        accounting_date: '2026-01-10',
        due_date: '2026-03-10',
        bill_reference: 'SHOULD-FAIL',
        lines: [],
    );

    expect(fn() => app(CreateVendorBillAction::class)->execute($vendorBillDto))
        ->toThrow(PeriodIsLockedException::class);
});

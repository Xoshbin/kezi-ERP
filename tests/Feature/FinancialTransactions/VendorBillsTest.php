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

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
});

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

    app(VendorBillService::class)->post($vendorBill, $this->user);

    $vendorBill->refresh();
    expect($vendorBill->status)->toBe(VendorBill::STATUS_POSTED);
    Event::assertDispatched(VendorBillConfirmed::class, fn($event) => $event->vendorBill->id === $vendorBill->id);
});

test('confirming a vendor bill generates the correct journal entry', function () {
    $product = Product::factory()->for($this->company)->create([
        'type' => \App\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => \App\Enums\Inventory\ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);
    $vendorBill = VendorBill::factory()->for($this->company)->create(['status' => 'draft']);

    // Since we now have a dedicated Action for this, we can simplify line creation
    \App\Models\VendorBillLine::factory()->for($vendorBill)->for($product)->create([
        'quantity' => 3,
        'unit_price' => Money::of(50, $this->company->currency->code), // Explicitly 50 IQD
    ]);

    // The service call remains the same
    app(VendorBillService::class)->post($vendorBill, $this->user);

    $this->assertDatabaseCount('journal_entries', 1);
    $journalEntry = JournalEntry::first();

    $expectedTotal = Money::of(150, $this->company->currency->code);
    expect($journalEntry->journal_id)->toBe($this->company->default_purchase_journal_id)
        ->and($journalEntry->total_debit)->toEqual($expectedTotal)
        ->and($journalEntry->total_credit)->toEqual($expectedTotal);

    expect($journalEntry->lines()->where('account_id', $this->inventoryAccount->id)->first()->debit)->toEqual($expectedTotal);
    expect($journalEntry->lines()->where('account_id', $this->stockInputAccount->id)->first()->credit)->toEqual($expectedTotal);
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
        'lock_type' => 'everything_date',
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

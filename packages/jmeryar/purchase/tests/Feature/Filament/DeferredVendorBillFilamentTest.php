<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\DeferredItem;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Enums\Purchases\VendorBillStatus;
use Jmeryar\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('can create a vendor bill with deferred expense dates on line items', function () {
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $expenseAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'expense']);
    $deferredAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_assets']);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Deferred Expense Item',
        'unit_price' => Money::of(1200, $this->company->currency->code),
        'expense_account_id' => $expenseAccount->id,
        'deferred_expense_account_id' => $deferredAccount->id,
    ]);

    // Ensure default purchase journal exists
    \Jmeryar\Accounting\Models\Journal::factory()->create(['company_id' => $this->company->id, 'type' => 'purchase']);
    \Jmeryar\Accounting\Models\Journal::factory()->create(['company_id' => $this->company->id, 'type' => 'miscellaneous']);

    livewire(CreateVendorBill::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'vendor_id' => $vendor->id,
            'currency_id' => $this->company->currency_id,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'bill_reference' => 'BILL-001',
        ])
        ->set('data.lines', [
            [
                'product_id' => $product->id,
                'description' => 'Annual Software License',
                'quantity' => 1,
                'unit_price' => 1200,
                'expense_account_id' => $expenseAccount->id,
                'deferred_start_date' => '2026-01-01',
                'deferred_end_date' => '2026-12-31',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $bill = VendorBill::first();
    $this->assertNotNull($bill);
    $this->assertEquals(VendorBillStatus::Draft, $bill->status);

    $line = $bill->lines->first();
    $this->assertEquals('2026-01-01', $line->deferred_start_date->format('Y-m-d'));
    $this->assertEquals('2026-12-31', $line->deferred_end_date->format('Y-m-d'));
});

it('creates deferred item when confirming vendor bill via UI', function () {
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $expenseAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'expense']);
    $deferredAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_assets']);

    // Ensure default purchase journal and accounts exist for posting
    $this->company->update([
        'default_purchase_journal_id' => \Jmeryar\Accounting\Models\Journal::factory()->create(['company_id' => $this->company->id, 'type' => 'purchase'])->id,
        'default_accounts_payable_id' => Account::factory()->create(['company_id' => $this->company->id, 'type' => 'payable'])->id,
        'default_tax_account_id' => Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_liabilities'])->id,
    ]);
    \Jmeryar\Accounting\Models\Journal::factory()->create(['company_id' => $this->company->id, 'type' => 'miscellaneous']);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'expense_account_id' => $expenseAccount->id,
        'deferred_expense_account_id' => $deferredAccount->id,
    ]);

    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'status' => VendorBillStatus::Draft,
        'total_amount' => Money::of(1200, $this->company->currency->code),
        'total_tax' => Money::of(0, $this->company->currency->code),
    ]);

    $bill->lines()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'description' => 'Deferred Expense Line',
        'quantity' => 1,
        'unit_price' => Money::of(1200, $this->company->currency->code),
        'subtotal' => Money::of(1200, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'deferred_start_date' => '2026-01-01',
        'deferred_end_date' => '2026-12-31',
        'expense_account_id' => $expenseAccount->id,
    ]);

    $bill->refresh();

    livewire(EditVendorBill::class, [
        'record' => $bill->getRouteKey(),
    ])
        ->callAction('post')
        ->assertHasNoErrors();

    $bill->refresh();
    $this->assertEquals(VendorBillStatus::Posted, $bill->status);

    $deferredItem = DeferredItem::where('source_type', \Jmeryar\Purchase\Models\VendorBillLine::class)
        ->where('source_id', $bill->lines->first()->id)
        ->first();

    $this->assertNotNull($deferredItem);
    $this->assertEquals($deferredAccount->id, $deferredItem->deferred_account_id);
});

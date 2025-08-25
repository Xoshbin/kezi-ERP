<?php

use App\Enums\Sales\InvoiceStatus;
use App\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);
});

it('can render the list page', function () {
    $this->get(InvoiceResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(InvoiceResource::getUrl('create'))->assertSuccessful();
});

it('can create an invoice', function () {
    /** @var \App\Models\Partner $customer */
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product Line', // Set a specific name to match the database assertion
        'unit_price' => \Brick\Money\Money::of(100, $this->company->currency->code), // Set a specific price for predictable total
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\Invoices\Pages\CreateInvoice::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'currency_id' => $this->company->currency_id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
        ])
        ->set('data.invoiceLines', [
            [
                'product_id' => $product->id,
                'description' => 'Test Product Line',
                'quantity' => 2,
                'unit_price' => $product->unit_price->getAmount()->toFloat(),
                'income_account_id' => $product->income_account_id,
                'tax_id' => null,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('invoices', [
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft->value,
    ]);

    $this->assertDatabaseHas('invoice_lines', [
        'product_id' => $product->id,
        'description' => 'Test Product Line',
        'quantity' => 2,
    ]);

    $invoice = Invoice::first();
    expect($invoice->total_amount->getAmount()->toFloat())->toBe(200.0);
});

it('can validate input on create', function () {
    livewire(\App\Filament\Clusters\Accounting\Resources\Invoices\Pages\CreateInvoice::class)
        ->fillForm([
            'customer_id' => null,
            'invoice_date' => null,
            'due_date' => null,
            'invoiceLines' => [],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'customer_id' => 'required',
            'invoice_date' => 'required',
            'due_date' => 'required',
            'invoiceLines' => 'min',
        ]);
});

it('can render the edit page', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(InvoiceResource::getUrl('edit', ['record' => $invoice]))
        ->assertSuccessful();
});

it('can edit an invoice', function () {
    $invoice = Invoice::factory()->withLines(1)->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Partner $newCustomer */
    $newCustomer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    // The mutateFormDataBeforeFill method in EditInvoice already handles
    // the conversion of line data with Money objects properly, so we don't need to override it
    livewire(\App\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice::class, [
        'record' => $invoice->getRouteKey(),
    ])
        ->fillForm([
            'customer_id' => $newCustomer->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'customer_id' => $newCustomer->id,
    ]);
});

it('can confirm an invoice', function () {
    $invoice = Invoice::factory()->withLines(1)->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Draft,
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice::class, [
        'record' => $invoice->getRouteKey(),
    ])
        ->callAction('confirm')
        ->assertHasNoErrors();

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Posted);
});

// TODO:: In future if you pland to add back the reset button just enable the test below and enable the action button in the Invoice resource
/*
 * Temprarily disable reset button since we are not sure about this feature wheter it's good or no
 * the feature is woking and passing tests */
// it('can reset an invoice to draft', function () {
//     // Create accounts for the journal entry
//     $receivableAccount = \App\Models\Account::factory()->create([
//         'company_id' => $this->company->id,
//         'type' => 'receivable',
//         'name' => 'Accounts Receivable',
//     ]);

//     $salesAccount = \App\Models\Account::factory()->create([
//         'company_id' => $this->company->id,
//         'type' => 'income',
//         'name' => 'Sales Revenue',
//     ]);

//     // Create a proper journal entry with lines for the invoice
//     $journalEntry = \App\Models\JournalEntry::factory()->create([
//         'company_id' => $this->company->id,
//         'is_posted' => true,
//     ]);

//     // Add lines to the journal entry
//     $journalEntry->lines()->create([
//         'account_id' => $receivableAccount->id,
//         'debit' => \Brick\Money\Money::of(100, $this->company->currency->code),
//         'credit' => \Brick\Money\Money::of(0, $this->company->currency->code),
//         'description' => 'Test line',
//     ]);

//     $journalEntry->lines()->create([
//         'account_id' => $salesAccount->id,
//         'debit' => \Brick\Money\Money::of(0, $this->company->currency->code),
//         'credit' => \Brick\Money\Money::of(100, $this->company->currency->code),
//         'description' => 'Test line 2',
//     ]);

//     $invoice = Invoice::factory()->withLines(1)->create([
//         'company_id' => $this->company->id,
//         'status' => InvoiceStatus::Posted,
//         'posted_at' => now(),
//         'invoice_number' => 'INV-00001',
//         'journal_entry_id' => $journalEntry->id,
//     ]);

//     $response = livewire(InvoiceResource\Pages\EditInvoice::class, [
//         'record' => $invoice->getRouteKey(),
//     ])
//         ->callAction('resetToDraft', data: [
//             'reason' => 'Test reason',
//         ]);

//     $response->assertHasNoErrors();

//     $invoice->refresh();
//     expect($invoice->status)->toBe(InvoiceStatus::Draft);
// });

<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\CreateInvoice;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\DeferredItem;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Models\Product;
use Jmeryar\Sales\Enums\Sales\InvoiceStatus;
use Jmeryar\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('can create an invoice with deferred revenue dates on line items', function () {
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    $incomeAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'income']);
    $deferredAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_liabilities']);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Deferred Service',
        'unit_price' => Money::of(1200, $this->company->currency->code),
        'income_account_id' => $incomeAccount->id,
        'deferred_revenue_account_id' => $deferredAccount->id,
    ]);

    // Ensure default sales journal exists
    \Jmeryar\Accounting\Models\Journal::factory()->create(['company_id' => $this->company->id, 'type' => 'sale']);
    \Jmeryar\Accounting\Models\Journal::factory()->create(['company_id' => $this->company->id, 'type' => 'miscellaneous']); // Required for processing if triggered

    livewire(CreateInvoice::class)
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
                'description' => 'Annual Subscription',
                'quantity' => 1,
                'unit_price' => 1200,
                'income_account_id' => $incomeAccount->id,
                'deferred_start_date' => '2026-01-01',
                'deferred_end_date' => '2026-12-31',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = Invoice::first();
    $this->assertNotNull($invoice);
    $this->assertEquals(InvoiceStatus::Draft, $invoice->status);

    $line = $invoice->invoiceLines->first();
    $this->assertEquals('2026-01-01', $line->deferred_start_date->format('Y-m-d'));
    $this->assertEquals('2026-12-31', $line->deferred_end_date->format('Y-m-d'));
});

it('creates deferred item when confirming invoice via UI', function () {
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    $incomeAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'income']);
    $deferredAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_liabilities']);
    // Ensure default sales journal and tax account exist for posting
    $this->company->update([
        'default_sales_journal_id' => \Jmeryar\Accounting\Models\Journal::factory()->create(['company_id' => $this->company->id, 'type' => 'sale'])->id,
        'default_accounts_receivable_id' => Account::factory()->create(['company_id' => $this->company->id, 'type' => 'receivable'])->id,
        'default_tax_account_id' => Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_liabilities'])->id,
    ]);
    \Jmeryar\Accounting\Models\Journal::factory()->create(['company_id' => $this->company->id, 'type' => 'miscellaneous']);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'income_account_id' => $incomeAccount->id,
        'deferred_revenue_account_id' => $deferredAccount->id,
    ]);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatus::Draft,
    ]);

    $invoice->invoiceLines()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'description' => 'Deferred Line',
        'quantity' => 1,
        'unit_price' => Money::of(1200, $this->company->currency->code),
        'subtotal' => Money::of(1200, $this->company->currency->code),
        'total_line_tax' => Money::of(0, $this->company->currency->code),
        'deferred_start_date' => '2026-01-01',
        'deferred_end_date' => '2026-12-31',
        'income_account_id' => $incomeAccount->id,
    ]);

    livewire(EditInvoice::class, [
        'record' => $invoice->getRouteKey(),
    ])
        ->callAction('post')
        ->assertHasNoErrors();

    $invoice->refresh();
    $this->assertEquals(InvoiceStatus::Posted, $invoice->status);

    $deferredItem = DeferredItem::where('source_type', \Jmeryar\Sales\Models\InvoiceLine::class)
        ->where('source_id', $invoice->invoiceLines->first()->id)
        ->first();

    $this->assertNotNull($deferredItem);

    // Debug output
    // dump('Income ID: ' . $incomeAccount->id);
    // dump('Deferred ID: ' . $deferredAccount->id);
    // dump('Deferred Item Account ID: ' . $deferredItem->deferred_account_id);
    // dump('Product Deferred ID: ' . $product->refresh()->deferred_revenue_account_id);

    $this->assertEquals($deferredAccount->id, $deferredItem->deferred_account_id);
});

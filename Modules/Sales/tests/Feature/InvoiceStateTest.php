<?php

use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Sales\Database\Factories\InvoiceFactory;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;

use function Pest\Livewire\livewire;

it('updates invoice status to posted in UI after confirmation', function () {
    // 1. Setup Data
    $company = \App\Models\Company::factory()->create();

    // Setup Accounting Defaults
    // Create Default AR Account for the company
    $arAccount = Account::factory()->create([
        'company_id' => $company->id,
        'type' => AccountType::Receivable,
        'code' => '1100', // ensure unique code within company
        'name' => 'Accounts Receivable',
    ]);

    // Create Default Sales Journal for the company
    $salesJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'type' => \Modules\Accounting\Enums\Accounting\JournalType::Sale,
        'name' => 'Customer Invoices',
        'short_code' => 'INV',
    ]);

    // Update Company to use these defaults
    $company->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_sales_journal_id' => $salesJournal->id,
    ]);

    // Create Invoice linked to this company
    // Currency ID is handled by factory or defaults to company currency if not specified?
    // InvoiceFactory usually sets currency_id. We should match company currency to avoid exchange rate complexity in this test.

    $invoice = Invoice::factory()
        ->withLines(1)
        ->create([
            'status' => InvoiceStatus::Draft,
            'company_id' => $company->id,
            'currency_id' => $company->currency_id,
        ]);

    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);
    \Filament\Facades\Filament::setTenant($company);

    // 2. Load Edit Page
    $component = livewire(EditInvoice::class, [
        'record' => $invoice->getRouteKey(),
    ]);

    // 3. Trigger Confirm Action
    $component->callAction('confirm');

    // 4. Verify Database State
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Posted);

    // 5. Verify Component State
    $component->assertActionHidden('confirm');
    $component->assertActionVisible('register_payment');
});

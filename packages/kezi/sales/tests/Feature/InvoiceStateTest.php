<?php

use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Sales\Database\Factories\InvoiceFactory;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;

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
        'type' => \Kezi\Accounting\Enums\Accounting\JournalType::Sale,
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

    // Assign Permissions
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(\Kezi\Foundation\Database\Seeders\RolesAndPermissionsSeeder::class);
    setPermissionsTeamId($company->id);
    $user->assignRole('super_admin');

    $this->actingAs($user);
    \Filament\Facades\Filament::setTenant($company);

    // 2. Load Edit Page
    $component = livewire(EditInvoice::class, [
        'record' => $invoice->getRouteKey(),
    ]);

    // 3. Trigger Post Action
    $component->callAction('post');

    // 4. Verify Database State
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Posted);

    // 5. Verify Component State
    // Since the component redirects, we cannot assert action visibility on the old instance reliably.
    $component->assertRedirect(EditInvoice::getUrl(['record' => $invoice->getRouteKey()]));
});

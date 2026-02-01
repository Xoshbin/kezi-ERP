<?php

namespace Jmeryar\Accounting\Tests\Feature\Filament\Clusters\Accounting\Resources\Invoices;

use App\Models\Company;
use Filament\Facades\Filament;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ListInvoices;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Models\Product;
use Jmeryar\Sales\Models\Invoice;

use function Pest\Livewire\livewire;

beforeEach(function () {
    // Setup Companies
    $this->companyA = Company::factory()->create(['name' => 'Company A', 'currency_id' => Currency::factory()->create(['code' => 'USD'])->id]);
    $this->companyB = Company::factory()->create(['name' => 'Company B', 'currency_id' => Currency::factory()->create(['code' => 'EUR'])->id]);

    // Setup Admin User for Company A
    $user = \App\Models\User::factory()->create();
    $user->companies()->attach($this->companyA);

    // Set team context BEFORE assigning permissions (required for company_id in model_has_permissions)
    setPermissionsTeamId($this->companyA->id);

    // Assign permissions
    $permissions = ['view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice'];
    foreach ($permissions as $permission) {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
    Filament::setTenant($this->companyA);

    // Setup Partner in Company A linking to Company B
    $this->partnerB = Partner::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'Subsidiary B Partner',
        'linked_company_id' => $this->companyB->id,
        'receivable_account_id' => Account::factory()->create([
            'company_id' => $this->companyA->id,
            'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Receivable,
        ])->id,
        'payable_account_id' => Account::factory()->create([
            'company_id' => $this->companyA->id,
            'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Payable,
        ])->id,
    ]);
});

it('auto-creates vendor bill in company B when invoice to partner B is confirmed in company A', function () {
    // 1. Setup Data for Invoice
    $journal = Journal::factory()->create(['company_id' => $this->companyA->id, 'type' => 'sale']);
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'income_account_id' => Account::factory()->create(['company_id' => $this->companyA->id, 'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Income])->id,
    ]);

    // 2. Create Draft Invoice via Factory (simulating UI form submission which creates draft first)
    $invoice = Invoice::factory()->create([
        'company_id' => $this->companyA->id,
        'customer_id' => $this->partnerB->id,
        'currency_id' => $this->companyA->currency_id,
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => \Jmeryar\Sales\Enums\Sales\InvoiceStatus::Draft,
    ]);

    // Create Invoice Line
    \Jmeryar\Sales\Models\InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'unit_price' => \Brick\Money\Money::of(100, 'USD'),
        'quantity' => 1,
        'income_account_id' => $product->income_account_id,
        'tax_id' => null, // Assuming no tax for simplicity or use factory default if nullable
    ]);

    // Recalculate totals to ensure consistency
    $invoice->calculateTotalsFromLines();
    $invoice->save();

    // Assign default sales journal to company
    $this->companyA->update(['default_sales_journal_id' => $journal->id]);

    // Create Reciprocal Vendor in Company B linked to Company A
    $partnerA = \Jmeryar\Foundation\Models\Partner::factory()->create([
        'company_id' => $this->companyB->id,
        'linked_company_id' => $this->companyA->id,
        'type' => \Jmeryar\Foundation\Enums\Partners\PartnerType::Vendor,
        'name' => 'Company A Vendor',
    ]);

    // Create Expense Account in Company B (needed for reciprocal bill creation)
    \Jmeryar\Accounting\Models\Account::factory()->create([
        'company_id' => $this->companyB->id,
        'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Expense,
        'name' => 'General Expense',
    ]);

    // 3. Trigger Confirm Action via Filament List Page
    $test = livewire(ListInvoices::class);
    $test->assertSuccessful();

    $test->callTableAction('confirm', $invoice);

    // Check for errors if status is not Posted
    if ($invoice->fresh()->status !== \Jmeryar\Sales\Enums\Sales\InvoiceStatus::Posted) {
        $test->assertNotified();
    }

    // 4. Assert Invoice Confirmed
    expect($invoice->fresh()->status)->toBe(\Jmeryar\Sales\Enums\Sales\InvoiceStatus::Posted);

    // 5. Assert Vendor Bill auto-creation in Company B
    // We need to check if a Vendor Bill exists in Company B with inter_company_source_id = $invoice->id
    $this->assertDatabaseHas('vendor_bills', [
        'company_id' => $this->companyB->id,
        'inter_company_source_id' => $invoice->id,
        'inter_company_source_type' => Invoice::class,
    ]);
});

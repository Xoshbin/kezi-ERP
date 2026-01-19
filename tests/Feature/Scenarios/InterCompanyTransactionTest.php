<?php

use App\Models\User;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\Consolidation\InterCompanyEliminationService;
use Modules\Foundation\Enums\Partners\PartnerType;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Purchase\Actions\Purchases\CreateVendorBillAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillDTO;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Modules\Sales\Actions\Sales\CreateInvoiceAction;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Tests\Builders\CompanyBuilder;

uses(Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    // Setup Company A (primary)
    $this->setupWithConfiguredCompany();
    $this->companyA = $this->company;
    $this->userA = $this->user;

    // Setup Company B (secondary)
    $this->companyB = CompanyBuilder::new()
        ->withDefaultAccounts()
        ->withDefaultJournals()
        ->create();

    $this->userB = User::factory()->create();
    $this->userB->companies()->attach($this->companyB);

    // Setup permissions for User B
    setPermissionsTeamId($this->companyB->id);
    $superAdminRoleB = \Spatie\Permission\Models\Role::firstOrCreate([
        'name' => 'super_admin',
        'company_id' => $this->companyB->id,
    ]);
    if ($superAdminRoleB->wasRecentlyCreated) {
        $superAdminRoleB->givePermissionTo(\Spatie\Permission\Models\Permission::all());
    }
    $this->userB->assignRole($superAdminRoleB);

    // Revert to Company A context
    setPermissionsTeamId($this->companyA->id);
    Filament::setTenant($this->companyA);
    $this->actingAs($this->userA);
});

it('correctly identifies inter-company balances when transactions occur between linked companies', function () {
    // 1. Link Company B as a Customer in Company A
    $partnerB_in_A = Partner::factory()->for($this->companyA)->create([
        'name' => 'Company B Link',
        'type' => PartnerType::Customer,
        'linked_company_id' => $this->companyB->id,
        'receivable_account_id' => Account::factory()->for($this->companyA)->create(['type' => AccountType::Receivable])->id,
        'payable_account_id' => Account::factory()->for($this->companyA)->create(['type' => AccountType::Payable])->id,
    ]);

    // 2. Link Company A as a Vendor in Company B
    $partnerA_in_B = Partner::factory()->for($this->companyB)->create([
        'name' => 'Company A Link',
        'type' => PartnerType::Vendor,
        'linked_company_id' => $this->companyA->id,
        'receivable_account_id' => Account::factory()->for($this->companyB)->create(['type' => AccountType::Receivable])->id,
        'payable_account_id' => Account::factory()->for($this->companyB)->create(['type' => AccountType::Payable])->id,
    ]);

    // 3. Create Product in Company A to sell
    $incomeAccountA = Account::factory()->for($this->companyA)->create(['type' => AccountType::Income]);
    $productA = Product::factory()->for($this->companyA)->create([
        'name' => 'Service A',
        'unit_price' => 1000, // $10.00
        'income_account_id' => $incomeAccountA->id,
    ]);

    // 4. Create Product in Company B to buy (Expense)
    $expenseAccountB = Account::factory()->for($this->companyB)->create(['type' => AccountType::Expense]);
    $productB = Product::factory()->for($this->companyB)->create([
        'name' => 'Service B',
        'unit_price' => 1000,
        'expense_account_id' => $expenseAccountB->id,
    ]);

    // 5. Company A: Create and Post Invoice to Company B
    $invoiceDto = new CreateInvoiceDTO(
        company_id: $this->companyA->id,
        customer_id: $partnerB_in_A->id,
        currency_id: $this->companyA->currency_id,
        invoice_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [
            new CreateInvoiceLineDTO(
                description: 'Consulting Services',
                quantity: 1,
                unit_price: Money::of(100, $this->companyA->currency->code),
                product_id: $productA->id,
                income_account_id: $productA->income_account_id,
                tax_id: null
            ),
        ],
        fiscal_position_id: null
    );

    $invoice = app(CreateInvoiceAction::class)->execute($invoiceDto);
    // Confirm/Post the invoice
    // Using InvoiceService to confirm as per memory "Invoice confirmation is performed via ... InvoiceService::confirm"
    app(\Modules\Sales\Services\InvoiceService::class)->confirm($invoice, $this->userA);

    expect($invoice->fresh()->status->value)->toBe(\Modules\Sales\Enums\Sales\InvoiceStatus::Posted->value);

    // 6. Company B: Create and Post Vendor Bill from Company A
    // Switch context to Company B
    setPermissionsTeamId($this->companyB->id);
    Filament::setTenant($this->companyB);
    $this->actingAs($this->userB);

    $billDto = new CreateVendorBillDTO(
        company_id: $this->companyB->id,
        vendor_id: $partnerA_in_B->id,
        currency_id: $this->companyB->currency_id,
        bill_reference: 'INV-' . $invoice->invoice_number, // Matching reference
        bill_date: now()->toDateString(),
        accounting_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [
            new CreateVendorBillLineDTO(
                product_id: $productB->id,
                description: 'Consulting Services Received',
                quantity: 1,
                unit_price: Money::of(100, $this->companyB->currency->code),
                expense_account_id: $productB->expense_account_id,
                tax_id: null,
                analytic_account_id: null
            ),
        ],
        created_by_user_id: $this->userB->id,
        payment_term_id: null,
        purchase_order_id: null,
        fiscal_position_id: null,
        incoterm: null
    );

    $bill = app(CreateVendorBillAction::class)->execute($billDto);
    // Post the bill
    app(\Modules\Purchase\Services\VendorBillService::class)->confirm($bill, $this->userB);

    expect($bill->fresh()->status->value)->toBe(\Modules\Purchase\Enums\Purchases\VendorBillStatus::Posted->value);

    // 7. Verify Inter-Company Elimination Service identifies these balances
    $service = app(InterCompanyEliminationService::class);
    $eliminationLines = $service->identifyInterCompanyBalances(
        [$this->companyA->id, $this->companyB->id],
        now()
    );

    // We expect at least:
    // - One receivable line from Company A (from the invoice)
    // - One payable line from Company B (from the bill)
    // The query filters for Receivable/Payable account types.

    $receivablesA = collect($eliminationLines)
        ->filter(fn ($line) => $line->company_id === $this->companyA->id && $line->account->type === AccountType::Receivable);

    $payablesB = collect($eliminationLines)
        ->filter(fn ($line) => $line->company_id === $this->companyB->id && $line->account->type === AccountType::Payable);

    expect($receivablesA)->not->toBeEmpty('Company A should have inter-company receivable lines')
        ->and($payablesB)->not->toBeEmpty('Company B should have inter-company payable lines');

    // Verify amounts match (conceptually 1000 minor units)
    // Note: This checks that the service correctly filters them.
    $amountA = $receivablesA->sum(fn ($line) => $line->debit->getMinorAmount()->toInt() - $line->credit->getMinorAmount()->toInt());
    $amountB = $payablesB->sum(fn ($line) => abs($line->credit->getMinorAmount()->toInt() - $line->debit->getMinorAmount()->toInt()));

    // Ensure we found the correct partner links
    $lineA = $receivablesA->first();
    expect($lineA->partner_id)->toBe($partnerB_in_A->id)
        ->and($lineA->partner->linked_company_id)->toBe($this->companyB->id);

    $lineB = $payablesB->first();
    expect($lineB->partner_id)->toBe($partnerA_in_B->id)
        ->and($lineB->partner->linked_company_id)->toBe($this->companyA->id);
});

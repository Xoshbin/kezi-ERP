<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\Tax;
use Jmeryar\Foundation\Models\Currency;

uses(RefreshDatabase::class);

test('generates iraq vat return correctly', function () {
    $company = Company::factory()->create();
    $currency = Currency::where('code', 'IQD')->first() ?? Currency::factory()->create(['code' => 'IQD']); // Iraq normally IQD, but let's assume functioning in IQD or converting
    $company->update(['currency_id' => $currency->id]);

    $taxAccountSales = Account::factory()->create(['company_id' => $company->id]);
    $taxAccountPurchase = Account::factory()->create(['company_id' => $company->id]);
    $journal = Journal::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create();

    // 1. Setup Taxes with Tags
    $taxSalesStandard = Tax::factory()->create([
        'company_id' => $company->id,
        'rate' => 15,
        'tax_account_id' => $taxAccountSales->id,
        'report_tag' => 'VAT_SALES_STD',
    ]);

    $taxPurchaseStandard = Tax::factory()->create([
        'company_id' => $company->id,
        'rate' => 15,
        'tax_account_id' => $taxAccountPurchase->id,
        'report_tag' => 'VAT_PURCHASE_STD',
    ]);

    // 2. Create Journal Entries with these taxes
    // Action helper
    $action = app(CreateJournalEntryAction::class);

    // Sales Entry: Gross 1150 (Net 1000 + 150 VAT)
    // JE: Dr AR 1150, Cr Income 1000, Cr Tax 150
    $action->execute(new CreateJournalEntryDTO(
        company_id: $company->id,
        journal_id: $journal->id,
        currency_id: $currency->id,
        entry_date: now(),
        reference: 'INV-001',
        description: 'Sales Invoice',
        source_type: null, source_id: null, created_by_user_id: $user->id,
        is_posted: true,
        exchange_rate: 1.0,
        lines: [
            new CreateJournalEntryLineDTO(
                account_id: Account::factory()->create()->id, // Income
                debit: Money::of(0, 'IQD'),
                credit: Money::of(1000, 'IQD'),
                description: 'Income',
                partner_id: null,
                analytic_account_id: null,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $taxAccountSales->id, // Tax
                debit: Money::of(0, 'IQD'),
                credit: Money::of(150, 'IQD'),
                description: 'VAT',
                partner_id: null, analytic_account_id: null,
                tax_id: $taxSalesStandard->id
                // Wait, JournalEntryLine model has tax_id?
                // CreateJournalEntryAction doesn't persist 'tax_id' automatically if not passed?
                // The DTO needs it? Or CreateJournalEntryLineDTO needs it?
                // Let's check CreateJournalEntryLineDTO.
            ),
            new CreateJournalEntryLineDTO(
                account_id: Account::factory()->create()->id, // AR
                debit: Money::of(1150, 'IQD'),
                credit: Money::of(0, 'IQD'),
                description: 'AR',
                partner_id: null,
                analytic_account_id: null,
            ),
        ]
    ));

    // 3. Execute Generator
    $start = now()->startOfMonth();
    $end = now()->endOfMonth();

    $generator = new \Jmeryar\Accounting\Services\Reports\Generators\IraqVATReturnGenerator;
    $report = $generator->generate($company, $start, $end);

    // 4. Verification
    // Sales Box (e.g., Box 01 for Sales Net, Box 06 for Sales Tax)
    // "VAT_SALES_STD" maps to which box? Let's assume per implementation.
    // Inspect IraqVATReturnGenerator to confirm keys.
    // For now assuming typical structure.

    expect($report['boxes'])->not->toBeEmpty();

    // Verify Net Sales (1000) - Box 1
    // Box 1 value is float
    expect($report['boxes']['1']['value'])->toBe(1000.0);

    // Verify Tax Output (150) - Box 2
    expect($report['boxes']['2']['value'])->toBe(150.0);

    // Initial check done.

    // Add Purchase Entry
    $action->execute(new CreateJournalEntryDTO(
        company_id: $company->id,
        journal_id: $journal->id,
        currency_id: $currency->id,
        entry_date: now(),
        reference: 'BILL-001',
        description: 'Vendor Bill',
        source_type: null, source_id: null, created_by_user_id: $user->id,
        is_posted: true,
        exchange_rate: 1.0,
        lines: [
            new CreateJournalEntryLineDTO(
                account_id: Account::factory()->create()->id, // Expense
                debit: Money::of(500, 'IQD'),
                credit: Money::of(0, 'IQD'),
                description: 'Expense',
                partner_id: null, analytic_account_id: null,
            ),
            new CreateJournalEntryLineDTO(
                account_id: $taxAccountPurchase->id, // Tax
                debit: Money::of(75, 'IQD'), // 15% of 500
                credit: Money::of(0, 'IQD'),
                description: 'Input VAT',
                partner_id: null, analytic_account_id: null,
                tax_id: $taxPurchaseStandard->id
            ),
            new CreateJournalEntryLineDTO(
                account_id: Account::factory()->create()->id, // AP
                debit: Money::of(0, 'IQD'),
                credit: Money::of(575, 'IQD'),
                description: 'AP',
                partner_id: null, analytic_account_id: null,
            ),
        ]
    ));

    // Regenerate
    $report = $generator->generate($company, $start, $end);

    // Check Sales unchanged
    expect($report['boxes']['1']['value'])->toBe(1000.0);
    expect($report['boxes']['2']['value'])->toBe(150.0);

    // Check Purchases
    // Box 3 Net Purchases: 500
    expect($report['boxes']['3']['value'])->toBe(500.0);
    // Box 4 Tax Input: 75
    expect($report['boxes']['4']['value'])->toBe(75.0);

    // Net Payable: 150 - 75 = 75
    expect($report['boxes']['5']['value'])->toBe(75.0);
});

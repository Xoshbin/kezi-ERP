<?php

namespace Tests\Feature\Actions\Accounting;

use App\Actions\Accounting\CreateJournalEntryForAdjustmentAction;
use App\Models\Account;
use App\Models\AdjustmentDocument;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    // Create currencies with proper exchange rates
    $this->iqd = Currency::firstOrCreate(
        ['code' => 'IQD'],
        [
            'name' => 'Iraqi Dinar',
            'symbol' => 'IQD',
            'exchange_rate' => 1.0, // Base currency
            'is_active' => true,
            'decimal_places' => 3
        ]
    );

    $this->usd = Currency::firstOrCreate(
        ['code' => 'USD'],
        [
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1460.0, // 1 USD = 1460 IQD
            'is_active' => true,
            'decimal_places' => 2
        ]
    );
});

test('adjustment document in foreign currency creates journal entry in base currency with original currency tracking', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup accounts
    $arAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Accounts Receivable',
        'code' => '1200',
        'type' => 'receivable'
    ]);

    $salesDiscountAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Sales Discount',
        'code' => '4200',
        'type' => 'income'
    ]);

    $taxAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Tax Payable',
        'code' => '2300',
        'type' => 'current_liabilities'
    ]);

    // Create sales journal
    $salesJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'Sales Journal',
        'currency_id' => $company->currency_id,
    ]);

    // Configure company
    $company->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_sales_discount_account_id' => $salesDiscountAccount->id,
        'default_tax_account_id' => $taxAccount->id,
        'default_sales_journal_id' => $salesJournal->id,
    ]);

    // Note: Adjustment documents don't have direct partner relationships

    // Create adjustment document (credit note) in USD
    $adjustmentDocument = AdjustmentDocument::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $this->usd->id, // Adjustment in USD
        'total_amount' => Money::of(110, 'USD'), // $110 USD total
        'total_tax' => Money::of(10, 'USD'), // $10 USD tax
        'reference_number' => 'CN-USD-001',
        'posted_at' => now(),
    ]);

    // Execute the action
    $action = app(CreateJournalEntryForAdjustmentAction::class);
    $journalEntry = $action->execute($adjustmentDocument, $user);

    // Verify journal entry is created in base currency (IQD)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must be in company base currency');

    // Calculate expected amounts in IQD
    // Total: $110 USD * 1460 = 160,600 IQD
    // Tax: $10 USD * 1460 = 14,600 IQD
    // Subtotal: $100 USD * 1460 = 146,000 IQD
    $expectedTotalIQD = Money::of(160600, 'IQD');
    $expectedTaxIQD = Money::of(14600, 'IQD');
    $expectedSubtotalIQD = Money::of(146000, 'IQD');

    expect($journalEntry->total_debit->isEqualTo($expectedTotalIQD))->toBeTrue('Total debit should be converted to IQD');
    expect($journalEntry->total_credit->isEqualTo($expectedTotalIQD))->toBeTrue('Total credit should be converted to IQD');

    // Verify journal entry lines store original currency information
    $journalEntry->load('lines');
    expect($journalEntry->lines)->toHaveCount(3); // Sales discount debit + Tax debit + AR credit

    foreach ($journalEntry->lines as $line) {
        // EXPECTED: Original currency amount should be stored as Money object in USD
        expect($line->original_currency_amount)->not->toBeNull('Original currency amount must be stored');
        expect($line->original_currency_amount)->toBeInstanceOf(\Brick\Money\Money::class, 'Should be Money object');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD', 'Original currency should be USD');

        // EXPECTED: Original currency ID should be stored
        expect($line->original_currency_id)->toBe($this->usd->id, 'Original currency ID should be USD');

        // EXPECTED: Exchange rate should be stored
        expect($line->exchange_rate_at_transaction)->toBe(1460.0, 'Exchange rate should be stored');
    }

    // Verify the sales discount debit line
    $salesDiscountLine = $journalEntry->lines->where('account_id', $salesDiscountAccount->id)->first();
    expect($salesDiscountLine->debit->isEqualTo($expectedSubtotalIQD))->toBeTrue('Sales discount debit should be converted to IQD');
    expect($salesDiscountLine->original_currency_amount->isEqualTo(Money::of(100, 'USD')))->toBeTrue('Original subtotal preserved');

    // Verify the tax debit line
    $taxLine = $journalEntry->lines->where('account_id', $taxAccount->id)->first();
    expect($taxLine->debit->isEqualTo($expectedTaxIQD))->toBeTrue('Tax debit should be converted to IQD');
    expect($taxLine->original_currency_amount->isEqualTo(Money::of(10, 'USD')))->toBeTrue('Original tax preserved');

    // Verify the AR credit line
    $arLine = $journalEntry->lines->where('account_id', $arAccount->id)->first();
    expect($arLine->credit->isEqualTo($expectedTotalIQD))->toBeTrue('AR credit should be converted to IQD');
    expect($arLine->original_currency_amount->isEqualTo(Money::of(110, 'USD')))->toBeTrue('Original total preserved');
});

test('adjustment document in same currency as company base currency handles original currency fields correctly', function () {
    // Setup: Company with IQD base currency, adjustment also in IQD
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup minimal required accounts and configuration
    $arAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'receivable']);
    $salesDiscountAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'income']);
    $taxAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'current_liabilities']);

    $salesJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
    ]);

    $company->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_sales_discount_account_id' => $salesDiscountAccount->id,
        'default_tax_account_id' => $taxAccount->id,
        'default_sales_journal_id' => $salesJournal->id,
    ]);

    // Create adjustment document in IQD (same as company currency)
    $adjustmentDocument = AdjustmentDocument::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $this->iqd->id, // Same currency as company
        'total_amount' => Money::of(160600, 'IQD'), // 160,600 IQD total
        'total_tax' => Money::of(14600, 'IQD'), // 14,600 IQD tax
        'reference_number' => 'CN-IQD-001',
        'posted_at' => now(),
    ]);

    $action = app(CreateJournalEntryForAdjustmentAction::class);
    $journalEntry = $action->execute($adjustmentDocument, $user);

    // Verify same currency handling
    expect($journalEntry->currency->code)->toBe('IQD');

    foreach ($journalEntry->lines as $line) {
        // For same currency, original amount should equal the IQD amount as Money object
        expect($line->original_currency_amount)->toBeInstanceOf(\Brick\Money\Money::class, 'Should be Money object');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('IQD', 'Original currency should be IQD');
        expect($line->original_currency_id)->toBe($this->iqd->id, 'Original currency ID should be IQD');
        expect($line->exchange_rate_at_transaction)->toBe(1.0, 'Exchange rate should be 1.0 for same currency');
    }
});

test('adjustment document uses company base currency for journal entry regardless of document currency', function () {
    // This test specifically verifies the currency conversion logic
    $company = Company::factory()->create(['currency_id' => $this->iqd->id]);
    $user = User::factory()->create();

    // Setup minimal accounts
    $arAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'receivable']);
    $salesDiscountAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'income']);
    $taxAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'current_liabilities']);

    $salesJournal = Journal::factory()->create(['company_id' => $company->id, 'currency_id' => $company->currency_id]);
    $company->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_sales_discount_account_id' => $salesDiscountAccount->id,
        'default_tax_account_id' => $taxAccount->id,
        'default_sales_journal_id' => $salesJournal->id,
    ]);

    // Create adjustment in EUR (different from both base and USD)
    $eur = Currency::firstOrCreate(['code' => 'EUR'], [
        'name' => 'Euro',
        'symbol' => '€',
        'exchange_rate' => 1600.0, // 1 EUR = 1600 IQD
        'is_active' => true,
        'decimal_places' => 2
    ]);

    $adjustmentDocument = AdjustmentDocument::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $eur->id, // Adjustment in EUR
        'total_amount' => Money::of(100, 'EUR'), // €100 EUR total
        'total_tax' => Money::of(0, 'EUR'), // No tax
        'reference_number' => 'CN-EUR-001',
        'posted_at' => now(),
    ]);

    $action = app(CreateJournalEntryForAdjustmentAction::class);
    $journalEntry = $action->execute($adjustmentDocument, $user);

    // Verify journal entry is in company base currency (IQD), not document currency (EUR)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must always be in company base currency');

    // Calculate expected amount: €100 EUR * 1600 = 160,000 IQD
    $expectedAmountIQD = Money::of(160000, 'IQD');
    expect($journalEntry->total_debit->isEqualTo($expectedAmountIQD))->toBeTrue();

    // Verify original currency tracking
    foreach ($journalEntry->lines as $line) {
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('EUR');
        expect($line->exchange_rate_at_transaction)->toBe(1600.0);
    }
});

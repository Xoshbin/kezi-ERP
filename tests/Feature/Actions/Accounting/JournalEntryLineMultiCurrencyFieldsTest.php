<?php

namespace Tests\Feature\Actions\Accounting;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\Account;
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

test('journal entry lines store original currency amount and exchange rate for foreign currency transactions', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup accounts
    $expenseAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Office Expenses',
        'code' => '5100',
        'type' => 'expense'
    ]);

    $apAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Accounts Payable',
        'code' => '2100',
        'type' => 'payable'
    ]);

    // Create journal
    $journal = Journal::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
    ]);

    // Create journal entry with foreign currency amounts
    $originalAmountUSD = Money::of(100, 'USD'); // Original amount in USD
    $convertedAmountIQD = Money::of(146000, 'IQD'); // Converted amount in IQD (100 * 1460)
    $exchangeRate = 1460.0;

    $journalEntryDTO = new CreateJournalEntryDTO(
        company_id: $company->id,
        journal_id: $journal->id,
        currency_id: $this->iqd->id, // Journal entry in base currency
        entry_date: now()->format('Y-m-d'),
        reference: 'TEST-USD-001',
        description: 'Test foreign currency entry',
        created_by_user_id: $user->id,
        is_posted: true,
        lines: [
            new CreateJournalEntryLineDTO(
                account_id: $expenseAccount->id,
                debit: $convertedAmountIQD,
                credit: Money::of(0, 'IQD'),
                description: 'Expense in USD converted to IQD',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $originalAmountUSD, // Original Money object in USD
                original_currency_id: $this->usd->id, // Original currency ID
                exchange_rate_at_transaction: $exchangeRate
            ),
            new CreateJournalEntryLineDTO(
                account_id: $apAccount->id,
                debit: Money::of(0, 'IQD'),
                credit: $convertedAmountIQD,
                description: 'AP in USD converted to IQD',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $originalAmountUSD, // Original Money object in USD
                original_currency_id: $this->usd->id, // Original currency ID
                exchange_rate_at_transaction: $exchangeRate
            ),
        ]
    );

    // Execute the action
    $journalEntry = app(CreateJournalEntryAction::class)->execute($journalEntryDTO);

    // Verify journal entry is created in base currency
    expect($journalEntry->currency->code)->toBe('IQD');

    // Verify journal entry lines store original currency information
    $journalEntry->load('lines');
    expect($journalEntry->lines)->toHaveCount(2);

    foreach ($journalEntry->lines as $line) {
        // EXPECTED: Original currency amount should be stored as Money object in USD
        expect($line->original_currency_amount)->not->toBeNull('Original currency amount must be stored');
        expect($line->original_currency_amount)->toBeInstanceOf(\Brick\Money\Money::class, 'Should be Money object');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD', 'Original currency should be USD');
        expect($line->original_currency_amount->isEqualTo($originalAmountUSD))->toBeTrue('Original amount should match the USD amount');

        // EXPECTED: Original currency ID should be stored
        expect($line->original_currency_id)->toBe($this->usd->id, 'Original currency ID should be USD');

        // EXPECTED: Exchange rate should be stored
        expect($line->exchange_rate_at_transaction)->toBe($exchangeRate, 'Exchange rate should be stored');
    }

    // Verify the debit line specifically
    $debitLine = $journalEntry->lines->where('account_id', $expenseAccount->id)->first();
    expect($debitLine->debit->isEqualTo($convertedAmountIQD))->toBeTrue('Debit should be in base currency');
    expect($debitLine->original_currency_amount->isEqualTo($originalAmountUSD))->toBeTrue('Original amount preserved');

    // Verify the credit line specifically
    $creditLine = $journalEntry->lines->where('account_id', $apAccount->id)->first();
    expect($creditLine->credit->isEqualTo($convertedAmountIQD))->toBeTrue('Credit should be in base currency');
    expect($creditLine->original_currency_amount->isEqualTo($originalAmountUSD))->toBeTrue('Original amount preserved');
});

test('journal entry lines handle same currency transactions correctly', function () {
    // Setup: Company with IQD base currency, transaction also in IQD
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup accounts
    $expenseAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
    $apAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'payable']);
    $journal = Journal::factory()->create(['company_id' => $company->id, 'currency_id' => $company->currency_id]);

    // Create journal entry with same currency (no conversion needed)
    $amountIQD = Money::of(146000, 'IQD');
    $exchangeRate = 1.0; // Same currency, no conversion

    $journalEntryDTO = new CreateJournalEntryDTO(
        company_id: $company->id,
        journal_id: $journal->id,
        currency_id: $this->iqd->id,
        entry_date: now()->format('Y-m-d'),
        reference: 'TEST-IQD-001',
        description: 'Test same currency entry',
        created_by_user_id: $user->id,
        is_posted: true,
        lines: [
            new CreateJournalEntryLineDTO(
                account_id: $expenseAccount->id,
                debit: $amountIQD,
                credit: Money::of(0, 'IQD'),
                description: 'Expense in IQD',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $amountIQD, // Same Money object
                original_currency_id: $this->iqd->id, // Same currency ID
                exchange_rate_at_transaction: $exchangeRate
            ),
            new CreateJournalEntryLineDTO(
                account_id: $apAccount->id,
                debit: Money::of(0, 'IQD'),
                credit: $amountIQD,
                description: 'AP in IQD',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $amountIQD, // Same Money object
                original_currency_id: $this->iqd->id, // Same currency ID
                exchange_rate_at_transaction: $exchangeRate
            ),
        ]
    );

    $journalEntry = app(CreateJournalEntryAction::class)->execute($journalEntryDTO);

    // Verify same currency handling
    $line = $journalEntry->lines->first();
    expect($line->original_currency_amount->isEqualTo($amountIQD))->toBeTrue();
    expect($line->original_currency_id)->toBe($this->iqd->id);
    expect($line->exchange_rate_at_transaction)->toBe(1.0);
    expect($line->debit->isEqualTo($amountIQD))->toBeTrue();
});

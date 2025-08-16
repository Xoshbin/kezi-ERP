<?php

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Journal;
use App\Models\Account;
use App\Models\User;
use App\Services\JournalEntryService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('journal entry can store multi-currency amounts', function () {
    $this->setupWithConfiguredCompany();

    $foreignCurrency = Currency::factory()->create(['code' => 'EUR']);
    $journal = Journal::factory()->create(['company_id' => $this->company->id]);
    $account1 = Account::factory()->create(['company_id' => $this->company->id]);
    $account2 = Account::factory()->create(['company_id' => $this->company->id]);

    // Create exchange rate for the foreign currency
    \App\Models\CurrencyRate::factory()->create([
        'currency_id' => $foreignCurrency->id,
        'company_id' => $this->company->id,
        'rate' => 1.5,
        'effective_date' => Carbon::today(),
    ]);

    // Create line DTOs
    $lineDTOs = [
        new CreateJournalEntryLineDTO(
            account_id: $account1->id,
            debit: Money::of(100, 'EUR'),
            credit: Money::of(0, 'EUR'),
            description: 'Test debit line',
            partner_id: null,
            analytic_account_id: null
        ),
        new CreateJournalEntryLineDTO(
            account_id: $account2->id,
            debit: Money::of(0, 'EUR'),
            credit: Money::of(100, 'EUR'),
            description: 'Test credit line',
            partner_id: null,
            analytic_account_id: null
        ),
    ];

    // Create journal entry DTO
    $journalEntryDTO = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $journal->id,
        currency_id: $foreignCurrency->id,
        entry_date: Carbon::today()->format('Y-m-d'),
        reference: 'TEST-001',
        description: 'Multi-currency test entry',
        created_by_user_id: $this->user->id,
        is_posted: true,
        lines: $lineDTOs
    );

    // Create journal entry using the Action
    $journalEntry = app(CreateJournalEntryAction::class)->execute($journalEntryDTO);

    expect($journalEntry->currency_id)->toBe($foreignCurrency->id);
    expect($journalEntry->total_debit->getAmount()->toFloat())->toBe(100.0);
    expect($journalEntry->total_debit_company_currency->getAmount()->toFloat())->toBe(150.0);
    expect($journalEntry->exchange_rate_at_entry)->toBe('1.5000000000');
});

test('journal entry line can store multi-currency amounts', function () {
    $company = Company::factory()->create();
    $baseCurrency = $company->currency;
    $foreignCurrency = Currency::factory()->create(['code' => 'EUR']);

    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $foreignCurrency->id,
    ]);

    $account = Account::factory()->create(['company_id' => $company->id]);

    $line = JournalEntryLine::create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'currency_id' => $foreignCurrency->id,
        'debit' => Money::of(100, 'EUR'),
        'credit' => Money::zero('EUR'),
        'debit_company_currency' => Money::of(150, $baseCurrency->code),
        'credit_company_currency' => Money::zero($baseCurrency->code),
        'exchange_rate_at_transaction_decimal' => 1.5,
        'original_currency_amount' => Money::of(100, 'EUR'),
        'description' => 'Test line',
    ]);

    expect($line->currency_id)->toBe($foreignCurrency->id);
    expect($line->debit->getAmount()->toFloat())->toBe(100.0);
    expect($line->debit_company_currency->getAmount()->toFloat())->toBe(150.0);
    expect($line->exchange_rate_at_transaction_decimal)->toBe('1.5000000000');
});

test('journal entry line currency relationship works', function () {
    $company = Company::factory()->create();
    $foreignCurrency = Currency::factory()->create(['code' => 'EUR']);

    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $foreignCurrency->id,
    ]);

    $account = Account::factory()->create(['company_id' => $company->id]);

    $line = JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'currency_id' => $foreignCurrency->id,
    ]);

    expect($line->currency)->toBeInstanceOf(Currency::class);
    expect($line->currency->code)->toBe('EUR');
});

test('journal entry line can be created with explicit currency', function () {
    $company = Company::factory()->create();
    $foreignCurrency = Currency::factory()->create(['code' => 'EUR']);
    $user = User::factory()->create();

    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $foreignCurrency->id,
        'created_by_user_id' => $user->id,
    ]);

    $account = Account::factory()->create(['company_id' => $company->id]);

    // Create line with explicit currency_id to avoid MoneyCast issues
    $line = new JournalEntryLine([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $account->id,
        'currency_id' => $foreignCurrency->id,
        'description' => 'Test line',
    ]);

    // Set amounts as integers (minor amounts)
    $line->setAttribute('debit', 10000); // 100.00 EUR
    $line->setAttribute('credit', 0);
    $line->setAttribute('debit_company_currency', 15000); // 150.00 in company currency
    $line->setAttribute('credit_company_currency', 0);
    $line->setAttribute('exchange_rate_at_transaction_decimal', 1.5);
    $line->setAttribute('original_currency_amount', 10000);

    $line->save();

    expect($line->currency_id)->toBe($foreignCurrency->id);
    expect($line->debit->getAmount()->toFloat())->toBe(10000.0); // Minor amount stored as-is
    expect($line->debit_company_currency->getAmount()->toFloat())->toBe(15000.0); // Minor amount stored as-is
});

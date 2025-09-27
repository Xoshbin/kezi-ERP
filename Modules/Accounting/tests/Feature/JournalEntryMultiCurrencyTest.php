<?php

namespace Tests\Feature\Accounting;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Enums\Accounting\AccountType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->usdCurrency = \Modules\Foundation\Models\Currency::factory()->create(['code' => 'USD']);
    $this->journal = Journal::factory()->create(['company_id' => $this->company->id]);
    $this->account1 = \Modules\Accounting\Models\Account::factory()->create(['company_id' => $this->company->id]);
    $this->account2 = \Modules\Accounting\Models\Account::factory()->create(['company_id' => $this->company->id]);

    // Create exchange rate for the foreign currency (use yesterday to ensure it's available)
    $this->entryDate = Carbon::today()->format('Y-m-d');
    $this->rateDate = Carbon::yesterday()->format('Y-m-d');
    $this->currencyRate = \Modules\Foundation\Models\CurrencyRate::create([
        'currency_id' => $this->usdCurrency->id,
        'company_id' => $this->company->id,
        'rate' => 1460,
        'effective_date' => $this->rateDate,
        'source' => 'manual',
    ]);

    // Create standard accounts
    $this->receivableAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'receivable']);
    $this->revenueAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'income']);
});

test('it creates a journal entry in a foreign currency correctly', function () {
    // Arrange: Prepare the DTO with USD amounts
    $dto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $this->journal->id,
        currency_id: $this->usdCurrency->id, // The user is entering this transaction in USD
        entry_date: now()->toDateString(),
        reference: 'Test USD Entry',
        description: 'Capital injection in USD',
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: [
            new CreateJournalEntryLineDTO(
                account_id: $this->receivableAccount->id,
                debit: Money::ofMinor(10000, 'USD'), // $100.00
                credit: Money::zero('USD'),
                description: 'Debit in USD',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: Money::ofMinor(10000, 'USD'),
                exchange_rate_at_transaction: 1460.0
            ),
            new CreateJournalEntryLineDTO(
                account_id: $this->revenueAccount->id,
                debit: Money::zero('USD'),
                credit: Money::ofMinor(10000, 'USD'), // $100.00
                description: 'Credit in USD',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: Money::ofMinor(10000, 'USD'),
                exchange_rate_at_transaction: 1460.0
            ),
        ]
    );

    // Act: Execute the action
    $journalEntry = resolve(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class)->execute($dto);
    $journalEntry->refresh(); // Reload from DB to ensure data is persisted correctly

    $debitLine = $journalEntry->lines->first(function ($line) {
        return $line->getAttributes()['debit'] > 0;
    });
    $creditLine = $journalEntry->lines->first(function ($line) {
        return $line->getAttributes()['credit'] > 0;
    });

    // Assert: Verify all parts of the architecture

    // 1. Assert the Journal Entry Header is correct
    expect($journalEntry->currency_id)->toBe($this->usdCurrency->id)
        ->and($journalEntry->currency->code)->toBe('USD');

    // 2. Assert the Header TOTALS are in the COMPANY'S BASE CURRENCY (IQD)
    // USD $100.00 * 1460 rate = 146,000.000 IQD = 146,000,000 fils (IQD has 3 decimal places)
    $expectedBaseAmount = 100 * 1460 * 1000; // 146,000,000 IQD fils
    expect($journalEntry->total_debit->getMinorAmount()->toInt())->toBe($expectedBaseAmount);
    expect($journalEntry->total_credit->getMinorAmount()->toInt())->toBe($expectedBaseAmount);
    expect($journalEntry->total_debit->getCurrency()->getCurrencyCode())->toBe($this->company->currency->code);

    // 3. Assert the Journal Entry LINE amounts are in the COMPANY'S BASE CURRENCY (IQD)
    expect($debitLine->debit->getMinorAmount()->toInt())->toBe($expectedBaseAmount);
    expect($debitLine->debit->getCurrency()->getCurrencyCode())->toBe($this->company->currency->code);
    expect($creditLine->credit->getMinorAmount()->toInt())->toBe($expectedBaseAmount);
    expect($creditLine->credit->getCurrency()->getCurrencyCode())->toBe($this->company->currency->code);

    // 4. Assert the LINE'S original amount is stored correctly in the FOREIGN CURRENCY (USD)
    expect($debitLine->original_currency_amount->getMinorAmount()->toInt())->toBe(10000); // $100.00 in minor units
    expect($debitLine->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD');

    // 5. Assert the exchange rate was stored correctly
    expect((float) $debitLine->exchange_rate_at_transaction)->toBe(1460.0);
});

test('it blocks posting to a currency-locked account with the wrong currency', function () {
    // Arrange: Create a bank account that is explicitly locked to USD
    $usdBankAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash,
        'currency_id' => $this->usdCurrency->id,
    ]);

    // Arrange: Attempt to create a journal entry in the BASE CURRENCY (IQD)
    // and post it to the USD-locked account. This should be forbidden.
    $dto = new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $this->journal->id,
        currency_id: $this->company->currency->id, // Entry is in IQD
        entry_date: now()->toDateString(),
        reference: 'Test Invalid Entry',
        description: 'An attempt to post IQD to a USD account',
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: [
            new CreateJournalEntryLineDTO(
                account_id: $usdBankAccount->id, // The USD-locked account
                debit: Money::ofMinor(100000, 'IQD'),
                credit: Money::zero('IQD'),
                description: 'Debit in IQD',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: Money::ofMinor(100000, 'IQD'), // Original amount is also IQD
                exchange_rate_at_transaction: 1.0
            ),
            new CreateJournalEntryLineDTO(
                account_id: $this->revenueAccount->id, // Balancing line
                debit: Money::zero('IQD'),
                credit: Money::ofMinor(100000, 'IQD'),
                description: 'Credit in IQD',
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: Money::ofMinor(100000, 'IQD'),
                exchange_rate_at_transaction: 1.0
            ),
        ]
    );

    // Act & Assert: Expect a ValidationException to be thrown by the Action
    expect(fn () => resolve(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class)->execute($dto))
        ->toThrow(ValidationException::class);
});

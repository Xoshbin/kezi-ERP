<?php

namespace Tests\Feature\Filament\JournalEntry;


use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Enums\Accounting\AccountType;
use App\Filament\Resources\JournalEntryResource\Pages\CreateJournalEntry;
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

test('currency mismatch error is resolved when Money objects are passed from form', function () {
    // This test specifically addresses the original error:
    // "The monies do not share the same currency: expected IQD, got USD"

    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    // Setup accounts
    $expenseAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Office Expenses',
        'code' => '6100',
        'type' => 'expense'
    ]);

    $bankAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank Account',
        'code' => '1100',
        'type' => 'bank_and_cash'
    ]);

    // Create journal
    $journal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'General Journal',
        'currency_id' => $company->currency_id,
    ]);

    // Set current tenant
    \Filament\Facades\Filament::setTenant($company);

    // This simulates the exact scenario that was causing the error:
    // User selects USD currency, MoneyInput creates Money objects in USD,
    // but CreateJournalEntryAction expects all Money objects to be in IQD
    $formData = [
        'journal_id' => $journal->id,
        'currency_id' => $this->usd->id, // User selects USD
        'entry_date' => '2025-01-15',
        'reference' => 'TEST-CURRENCY-MISMATCH',
        'description' => 'Test currency mismatch fix',
        'lines' => [
            [
                'account_id' => $expenseAccount->id,
                'debit' => Money::of(100, 'USD'), // USD Money object from MoneyInput
                'credit' => Money::of(0, 'USD'),   // USD Money object from MoneyInput
                'description' => 'Expense in USD',
                'partner_id' => null,
                'analytic_account_id' => null,
            ],
            [
                'account_id' => $bankAccount->id,
                'debit' => Money::of(0, 'USD'),   // USD Money object from MoneyInput
                'credit' => Money::of(100, 'USD'), // USD Money object from MoneyInput
                'description' => 'Bank payment',
                'partner_id' => null,
                'analytic_account_id' => null,
            ],
        ],
    ];

    // Use reflection to test the protected methods
    $createPage = new CreateJournalEntry();
    $reflection = new \ReflectionClass($createPage);

    // Test mutateFormDataBeforeCreate - this should convert USD Money objects to IQD
    $mutateMethod = $reflection->getMethod('mutateFormDataBeforeCreate');
    $mutateMethod->setAccessible(true);
    $mutatedData = $mutateMethod->invoke($createPage, $formData);

    // Verify that all Money objects in the DTOs are now in IQD (base currency)
    expect($mutatedData['lines'])->toBeArray()->toHaveCount(2);

    foreach ($mutatedData['lines'] as $lineDTO) {
        expect($lineDTO)->toBeInstanceOf(CreateJournalEntryLineDTO::class);
        expect($lineDTO->debit->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($lineDTO->credit->getCurrency()->getCurrencyCode())->toBe('IQD');
    }

    // Test handleRecordCreation - this should NOT throw a currency mismatch error
    $handleMethod = $reflection->getMethod('handleRecordCreation');
    $handleMethod->setAccessible(true);

    // This should complete successfully without throwing MoneyMismatchException
    $journalEntry = $handleMethod->invoke($createPage, $mutatedData);

    // Verify the journal entry was created successfully
    expect($journalEntry)->toBeInstanceOf(\App\Models\JournalEntry::class);
    expect($journalEntry->currency_id)->toBe($this->iqd->id); // Should be in base currency
    expect($journalEntry->lines)->toHaveCount(2);

    // Verify all journal entry lines are in IQD
    foreach ($journalEntry->lines as $line) {
        expect($line->debit->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($line->credit->getCurrency()->getCurrencyCode())->toBe('IQD');
    }
});

test('manual journal entry creation handles Money objects from MoneyInput components', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    // Setup accounts
    $expenseAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Office Expenses',
        'code' => '6100',
        'type' => 'expense'
    ]);

    $bankAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank Account',
        'code' => '1100',
        'type' => 'bank_and_cash'
    ]);

    // Create journal
    $journal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'General Journal',
        'currency_id' => $company->currency_id,
    ]);

    // Set current tenant
    \Filament\Facades\Filament::setTenant($company);

    // Simulate form data where MoneyInput components have already converted values to Money objects
    // This simulates the actual behavior when the form is submitted through the web interface
    $formData = [
        'journal_id' => $journal->id,
        'currency_id' => $this->usd->id, // User selects USD currency
        'entry_date' => '2025-01-15',
        'reference' => 'TEST-USD-MONEY-001',
        'description' => 'Test expense with Money objects',
        'lines' => [
            [
                'account_id' => $expenseAccount->id,
                'debit' => Money::of(100, 'USD'), // Money object in USD (from MoneyInput)
                'credit' => Money::of(0, 'USD'),   // Money object in USD (from MoneyInput)
                'description' => 'Office supplies',
                'partner_id' => null,
                'analytic_account_id' => null,
            ],
            [
                'account_id' => $bankAccount->id,
                'debit' => Money::of(0, 'USD'),   // Money object in USD (from MoneyInput)
                'credit' => Money::of(100, 'USD'), // Money object in USD (from MoneyInput)
                'description' => 'Bank payment',
                'partner_id' => null,
                'analytic_account_id' => null,
            ],
        ],
    ];

    // Use reflection to test the protected mutateFormDataBeforeCreate method
    $createPage = new CreateJournalEntry();
    $reflection = new \ReflectionClass($createPage);
    $method = $reflection->getMethod('mutateFormDataBeforeCreate');
    $method->setAccessible(true);

    // Test the mutateFormDataBeforeCreate method with Money objects
    $mutatedData = $method->invoke($createPage, $formData);

    // Verify that lines were converted to DTOs
    expect($mutatedData['lines'])->toBeArray()->toHaveCount(2);

    // Check first line (expense)
    $firstLine = $mutatedData['lines'][0];
    expect($firstLine)->toBeInstanceOf(CreateJournalEntryLineDTO::class);

    // Verify the converted amounts are in IQD (base currency)
    expect($firstLine->debit->getCurrency()->getCurrencyCode())->toBe('IQD');
    expect($firstLine->credit->getCurrency()->getCurrencyCode())->toBe('IQD');

    // Verify the conversion calculation (100 USD * 1460 = 146,000 IQD)
    expect($firstLine->debit->getAmount()->toInt())->toBe(146000);
    expect($firstLine->credit->getAmount()->toInt())->toBe(0);

    // Verify original currency tracking
    expect($firstLine->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD');
    expect($firstLine->original_currency_amount->getAmount()->toInt())->toBe(100);
    expect($firstLine->original_currency_id)->toBe($this->usd->id);
    expect($firstLine->exchange_rate_at_transaction)->toBe(1460.0);

    // Check second line (cash)
    $secondLine = $mutatedData['lines'][1];
    expect($secondLine)->toBeInstanceOf(CreateJournalEntryLineDTO::class);

    // Verify the converted amounts are in IQD
    expect($secondLine->debit->getCurrency()->getCurrencyCode())->toBe('IQD');
    expect($secondLine->credit->getCurrency()->getCurrencyCode())->toBe('IQD');

    // Verify the conversion calculation
    expect($secondLine->debit->getAmount()->toInt())->toBe(0);
    expect($secondLine->credit->getAmount()->toInt())->toBe(146000);

    // Verify original currency tracking
    expect($secondLine->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD');
    expect($secondLine->original_currency_amount->getAmount()->toInt())->toBe(100);
    expect($secondLine->original_currency_id)->toBe($this->usd->id);
    expect($secondLine->exchange_rate_at_transaction)->toBe(1460.0);

    // Test the handleRecordCreation method using reflection
    $handleMethod = $reflection->getMethod('handleRecordCreation');
    $handleMethod->setAccessible(true);
    $journalEntry = $handleMethod->invoke($createPage, $mutatedData);

    // Verify the journal entry was created successfully
    expect($journalEntry)->toBeInstanceOf(\App\Models\JournalEntry::class);
    expect($journalEntry->company_id)->toBe($company->id);
    expect($journalEntry->journal_id)->toBe($journal->id);
    expect($journalEntry->currency_id)->toBe($this->iqd->id); // Should be in base currency
    expect($journalEntry->reference)->toBe('TEST-USD-MONEY-001');
    expect($journalEntry->description)->toBe('Test expense with Money objects');

    // Verify the journal entry lines
    expect($journalEntry->lines)->toHaveCount(2);

    $expenseLine = $journalEntry->lines->where('account_id', $expenseAccount->id)->first();
    expect($expenseLine)->not->toBeNull();
    expect($expenseLine->debit->getAmount()->toInt())->toBe(146000);
    expect($expenseLine->credit->getAmount()->toInt())->toBe(0);
    expect($expenseLine->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD');
    expect($expenseLine->original_currency_amount->getAmount()->toInt())->toBe(100);
    expect($expenseLine->original_currency_id)->toBe($this->usd->id);
    expect($expenseLine->exchange_rate_at_transaction)->toBe(1460.0);

    $cashLine = $journalEntry->lines->where('account_id', $bankAccount->id)->first();
    expect($cashLine)->not->toBeNull();
    expect($cashLine->debit->getAmount()->toInt())->toBe(0);
    expect($cashLine->credit->getAmount()->toInt())->toBe(146000);
    expect($cashLine->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD');
    expect($cashLine->original_currency_amount->getAmount()->toInt())->toBe(100);
    expect($cashLine->original_currency_id)->toBe($this->usd->id);
    expect($cashLine->exchange_rate_at_transaction)->toBe(1460.0);
});

test('manual journal entry creation in foreign currency should convert to base currency', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    // Setup accounts
    $expenseAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Office Expenses',
        'code' => '6100',
        'type' => 'expense'
    ]);

    $bankAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank Account',
        'code' => '1100',
        'type' => 'bank_and_cash'
    ]);

    // Create journal
    $journal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'General Journal',
        'currency_id' => $company->currency_id,
    ]);

    // Set current tenant
    \Filament\Facades\Filament::setTenant($company);

    // Simulate manual journal entry creation through Filament
    // User selects USD currency and enters amounts in USD
    $formData = [
        'journal_id' => $journal->id,
        'currency_id' => $this->usd->id, // User selects USD currency
        'entry_date' => '2025-01-15',
        'reference' => 'TEST-USD-001',
        'description' => 'Test expense in USD',
        'lines' => [
            [
                'account_id' => $expenseAccount->id,
                'debit' => 100, // $100 USD
                'credit' => 0,
                'description' => 'Office supplies',
                'partner_id' => null,
                'analytic_account_id' => null,
            ],
            [
                'account_id' => $bankAccount->id,
                'debit' => 0,
                'credit' => 100, // $100 USD
                'description' => 'Bank payment',
                'partner_id' => null,
                'analytic_account_id' => null,
            ],
        ],
    ];

    // Simulate the UPDATED Filament page logic manually
    // This replicates what the FIXED CreateJournalEntry::mutateFormDataBeforeCreate does
    $lineDTOs = [];
    $selectedCurrency = \App\Models\Currency::find($formData['currency_id']);
    $baseCurrency = $company->currency;

    // Determine exchange rate for conversion
    $exchangeRate = ($baseCurrency->id === $selectedCurrency->id) ? 1.0 : $selectedCurrency->exchange_rate;

    foreach ($formData['lines'] as $line) {
        // Create original amounts in selected currency
        $originalDebit = Money::of($line['debit'] ?? 0, $selectedCurrency->code);
        $originalCredit = Money::of($line['credit'] ?? 0, $selectedCurrency->code);

        // Convert amounts to company base currency
        $convertedDebit = Money::of(
            $originalDebit->getAmount()->multipliedBy($exchangeRate),
            $baseCurrency->code,
            null,
            \Brick\Math\RoundingMode::HALF_UP
        );
        $convertedCredit = Money::of(
            $originalCredit->getAmount()->multipliedBy($exchangeRate),
            $baseCurrency->code,
            null,
            \Brick\Math\RoundingMode::HALF_UP
        );

        // Determine which original amount to store (the non-zero one)
        $originalAmount = $originalDebit->isPositive() ? $originalDebit : $originalCredit;

        $lineDTOs[] = new \App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO(
            account_id: $line['account_id'],
            debit: $convertedDebit,
            credit: $convertedCredit,
            description: $line['description'],
            partner_id: $line['partner_id'],
            analytic_account_id: $line['analytic_account_id'],
            original_currency_amount: $originalAmount,
            original_currency_id: $selectedCurrency->id,
            exchange_rate_at_transaction: $exchangeRate
        );
    }

    // Create the DTO as the FIXED Filament page does (using company base currency)
    $journalEntryDTO = new \App\DataTransferObjects\Accounting\CreateJournalEntryDTO(
        company_id: $company->id,
        journal_id: $formData['journal_id'],
        currency_id: $baseCurrency->id, // Always use company base currency
        entry_date: $formData['entry_date'],
        reference: $formData['reference'],
        description: $formData['description'],
        created_by_user_id: $user->id,
        is_posted: false,
        lines: $lineDTOs
    );

    // Execute the action
    $journalEntry = app(\App\Actions\Accounting\CreateJournalEntryAction::class)->execute($journalEntryDTO);

    // EXPECTED BEHAVIOR (AFTER FIX): Journal entry is created in company base currency (IQD)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must be in company base currency');
    expect($journalEntry->total_debit->getCurrency()->getCurrencyCode())->toBe('IQD');

    // Expected amount: $100 USD * 1460 = 146,000 IQD
    $expectedAmountIQD = Money::of(146000, 'IQD');
    expect($journalEntry->total_debit->isEqualTo($expectedAmountIQD))->toBeTrue('Total debit should be converted to IQD');
    expect($journalEntry->total_credit->isEqualTo($expectedAmountIQD))->toBeTrue('Total credit should be converted to IQD');

    // Check journal entry lines
    $journalEntry->load('lines');
    foreach ($journalEntry->lines as $line) {
        expect($line->debit->getCurrency()->getCurrencyCode())->toBe('IQD', 'Lines should be in company base currency');
        expect($line->credit->getCurrency()->getCurrencyCode())->toBe('IQD', 'Lines should be in company base currency');

        // EXPECTED BEHAVIOR: Original currency tracking should preserve USD information
        expect($line->original_currency_amount)->not->toBeNull('Original currency amount must be stored');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD', 'Original currency should be USD');
        expect($line->original_currency_amount->isEqualTo(Money::of(100, 'USD')))->toBeTrue('Original amount should be $100 USD');
        expect($line->original_currency_id)->toBe($this->usd->id, 'Original currency ID should be USD');
        expect($line->exchange_rate_at_transaction)->toBe(1460.0, 'Exchange rate should be 1460.0');
    }

    // Verify individual line amounts
    $debitLine = $journalEntry->lines->filter(fn($line) => $line->debit->isPositive())->first();
    $creditLine = $journalEntry->lines->filter(fn($line) => $line->credit->isPositive())->first();

    expect($debitLine->debit->isEqualTo($expectedAmountIQD))->toBeTrue('Debit line should be 146,000 IQD');
    expect($creditLine->credit->isEqualTo($expectedAmountIQD))->toBeTrue('Credit line should be 146,000 IQD');
});

test('manual journal entry creation in same currency as company should work correctly', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    // Setup accounts
    $expenseAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Office Expenses',
        'code' => '6100',
        'type' => 'expense'
    ]);

    $bankAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank Account',
        'code' => '1100',
        'type' => 'bank_and_cash'
    ]);

    // Create journal
    $journal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'General Journal',
        'currency_id' => $company->currency_id,
    ]);

    // Set current tenant
    \Filament\Facades\Filament::setTenant($company);

    // Simulate manual journal entry creation in company base currency
    $formData = [
        'journal_id' => $journal->id,
        'currency_id' => $this->iqd->id, // User selects company base currency
        'entry_date' => '2025-01-15',
        'reference' => 'TEST-IQD-001',
        'description' => 'Test expense in IQD',
        'lines' => [
            [
                'account_id' => $expenseAccount->id,
                'debit' => 146000, // 146,000 IQD
                'credit' => 0,
                'description' => 'Office supplies',
                'partner_id' => null,
                'analytic_account_id' => null,
            ],
            [
                'account_id' => $bankAccount->id,
                'debit' => 0,
                'credit' => 146000, // 146,000 IQD
                'description' => 'Bank payment',
                'partner_id' => null,
                'analytic_account_id' => null,
            ],
        ],
    ];

    // Simulate the Filament page logic manually
    $lineDTOs = [];
    $currency = \App\Models\Currency::find($formData['currency_id']);
    foreach ($formData['lines'] as $line) {
        $lineDTOs[] = new \App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO(
            account_id: $line['account_id'],
            debit: Money::of($line['debit'] ?? 0, $currency->code),
            credit: Money::of($line['credit'] ?? 0, $currency->code),
            description: $line['description'],
            partner_id: $line['partner_id'],
            analytic_account_id: $line['analytic_account_id']
        );
    }

    $journalEntryDTO = new \App\DataTransferObjects\Accounting\CreateJournalEntryDTO(
        company_id: $company->id,
        journal_id: $formData['journal_id'],
        currency_id: $formData['currency_id'],
        entry_date: $formData['entry_date'],
        reference: $formData['reference'],
        description: $formData['description'],
        created_by_user_id: $user->id,
        is_posted: false,
        lines: $lineDTOs
    );

    $journalEntry = app(\App\Actions\Accounting\CreateJournalEntryAction::class)->execute($journalEntryDTO);

    // This should work correctly since it's in the same currency
    expect($journalEntry->currency->code)->toBe('IQD');
    expect($journalEntry->total_debit->getCurrency()->getCurrencyCode())->toBe('IQD');
    expect($journalEntry->total_debit->isEqualTo(Money::of(146000, 'IQD')))->toBeTrue();

    // Check journal entry lines
    $journalEntry->load('lines');
    foreach ($journalEntry->lines as $line) {
        expect($line->debit->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($line->credit->getCurrency()->getCurrencyCode())->toBe('IQD');
    }
});

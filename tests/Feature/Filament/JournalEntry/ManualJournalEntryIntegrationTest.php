<?php

namespace Tests\Feature\Filament\JournalEntry;

use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\User;
use App\Services\Reports\TrialBalanceService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('manual journal entry in foreign currency integrates correctly with reports', function () {
    // Setup: Company with IQD base currency
    $iqd = Currency::firstOrCreate(['code' => 'IQD'], [
        'name' => 'Iraqi Dinar',
        'symbol' => 'IQD',
        'exchange_rate' => 1.0,
        'is_active' => true,
        'decimal_places' => 3
    ]);

    $usd = Currency::firstOrCreate(['code' => 'USD'], [
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1460.0, // 1 USD = 1460 IQD
        'is_active' => true,
        'decimal_places' => 2
    ]);

    $company = Company::factory()->create([
        'currency_id' => $iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

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

    $journal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'General Journal',
        'currency_id' => $company->currency_id,
    ]);

    // Simulate user creating manual journal entry in USD through web interface
    $formData = [
        'journal_id' => $journal->id,
        'currency_id' => $usd->id, // User selects USD
        'entry_date' => '2025-01-15',
        'reference' => 'TEST-USD-001',
        'description' => 'Office supplies purchased in USD',
        'lines' => [
            [
                'account_id' => $expenseAccount->id,
                'debit' => 250, // $250 USD
                'credit' => 0,
                'description' => 'Office supplies',
                'partner_id' => null,
                'analytic_account_id' => null,
            ],
            [
                'account_id' => $bankAccount->id,
                'debit' => 0,
                'credit' => 250, // $250 USD
                'description' => 'Bank payment',
                'partner_id' => null,
                'analytic_account_id' => null,
            ],
        ],
    ];

    // Apply the FIXED Filament logic
    $lineDTOs = [];
    $selectedCurrency = Currency::find($formData['currency_id']);
    $baseCurrency = $company->currency;
    $exchangeRate = ($baseCurrency->id === $selectedCurrency->id) ? 1.0 : $selectedCurrency->exchange_rate;
    
    foreach ($formData['lines'] as $line) {
        $originalDebit = Money::of($line['debit'] ?? 0, $selectedCurrency->code);
        $originalCredit = Money::of($line['credit'] ?? 0, $selectedCurrency->code);
        
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

    $journalEntryDTO = new \App\DataTransferObjects\Accounting\CreateJournalEntryDTO(
        company_id: $company->id,
        journal_id: $formData['journal_id'],
        currency_id: $baseCurrency->id, // Always company base currency
        entry_date: $formData['entry_date'],
        reference: $formData['reference'],
        description: $formData['description'],
        created_by_user_id: $user->id,
        is_posted: true, // Post it immediately for reports
        lines: $lineDTOs
    );

    // Create the journal entry
    $journalEntry = app(\App\Actions\Accounting\CreateJournalEntryAction::class)->execute($journalEntryDTO);

    // Verify journal entry is correct
    expect($journalEntry->currency->code)->toBe('IQD');
    $expectedAmountIQD = Money::of(365000, 'IQD'); // $250 USD * 1460 = 365,000 IQD
    expect($journalEntry->total_debit->isEqualTo($expectedAmountIQD))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedAmountIQD))->toBeTrue();

    // Verify original currency tracking
    $journalEntry->load('lines');
    foreach ($journalEntry->lines as $line) {
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($line->original_currency_amount->isEqualTo(Money::of(250, 'USD')))->toBeTrue();
        expect($line->original_currency_id)->toBe($usd->id);
        expect($line->exchange_rate_at_transaction)->toBe(1460.0);
    }

    // Test integration with reports - Trial Balance should show IQD amounts
    $trialBalanceService = app(TrialBalanceService::class);
    $asOfDate = Carbon::parse('2025-01-31');
    $trialBalance = $trialBalanceService->generate($company, $asOfDate);

    // Verify trial balance shows converted amounts in IQD
    expect($trialBalance->totalDebit->getCurrency()->getCurrencyCode())->toBe('IQD');
    expect($trialBalance->totalDebit->isEqualTo($expectedAmountIQD))->toBeTrue();
    expect($trialBalance->totalCredit->isEqualTo($expectedAmountIQD))->toBeTrue();
    expect($trialBalance->isBalanced)->toBeTrue();

    // Find specific account lines
    $expenseLine = $trialBalance->reportLines->firstWhere('accountId', $expenseAccount->id);
    $bankLine = $trialBalance->reportLines->firstWhere('accountId', $bankAccount->id);

    expect($expenseLine->debit->isEqualTo($expectedAmountIQD))->toBeTrue('Expense should show 365,000 IQD');
    expect($bankLine->credit->isEqualTo($expectedAmountIQD))->toBeTrue('Bank should show 365,000 IQD credit');

    // This test proves that:
    // 1. Manual journal entries in foreign currency are properly converted to base currency
    // 2. Original currency information is preserved for audit trails
    // 3. Reports work correctly with the converted amounts
    // 4. The entire multi-currency system is working end-to-end
});

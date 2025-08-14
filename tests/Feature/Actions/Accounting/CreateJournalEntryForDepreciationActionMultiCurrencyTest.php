<?php

namespace Tests\Feature\Actions\Accounting;

use App\Actions\Accounting\CreateJournalEntryForDepreciationAction;
use App\Enums\Assets\DepreciationEntryStatus;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Currency;
use App\Models\DepreciationEntry;
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

test('depreciation entry for foreign currency asset creates journal entry in base currency with original currency tracking', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup accounts
    $assetAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Fixed Assets',
        'code' => '1500',
        'type' => 'fixed_assets'
    ]);

    $depreciationExpenseAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Depreciation Expense',
        'code' => '6100',
        'type' => 'depreciation'
    ]);

    $accumulatedDepreciationAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Accumulated Depreciation',
        'code' => '1510',
        'type' => 'fixed_assets'
    ]);

    // Create depreciation journal
    $depreciationJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'Depreciation Journal',
        'currency_id' => $company->currency_id,
    ]);

    // Configure company
    $company->update([
        'default_depreciation_journal_id' => $depreciationJournal->id,
    ]);

    // Create asset purchased in USD
    $asset = Asset::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $this->usd->id, // Asset purchased in USD
        'purchase_value' => Money::of(6000, 'USD'), // $6,000 USD
        'salvage_value' => Money::of(600, 'USD'), // $600 USD
        'asset_account_id' => $assetAccount->id,
        'depreciation_expense_account_id' => $depreciationExpenseAccount->id,
        'accumulated_depreciation_account_id' => $accumulatedDepreciationAccount->id,
        'purchase_date' => now(),
        'useful_life_years' => 5,
    ]);

    // Create depreciation entry
    // Monthly depreciation = ($6,000 - $600) / (5 * 12) = $5,400 / 60 = $90 USD per month
    $monthlyDepreciationUSD = Money::of(90, 'USD');

    $depreciationEntry = DepreciationEntry::factory()->create([
        'asset_id' => $asset->id,
        'amount' => $monthlyDepreciationUSD, // Amount in asset's original currency (USD)
        'depreciation_date' => now()->addMonth(),
        'status' => DepreciationEntryStatus::Draft,
    ]);

    // Execute the action
    $action = app(CreateJournalEntryForDepreciationAction::class);
    $journalEntry = $action->execute($depreciationEntry, $user);

    // Verify journal entry is created in base currency (IQD)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must be in company base currency');

    // Calculate expected amount in IQD: $90 USD * 1460 = 131,400 IQD
    $expectedAmountIQD = Money::of(131400, 'IQD');
    expect($journalEntry->total_debit->isEqualTo($expectedAmountIQD))->toBeTrue('Total debit should be converted to IQD');
    expect($journalEntry->total_credit->isEqualTo($expectedAmountIQD))->toBeTrue('Total credit should be converted to IQD');

    // Verify journal entry lines store original currency information
    $journalEntry->load('lines');
    expect($journalEntry->lines)->toHaveCount(2); // Depreciation expense debit + Accumulated depreciation credit

    foreach ($journalEntry->lines as $line) {
        // EXPECTED: Original currency amount should be stored as Money object in USD
        expect($line->original_currency_amount)->not->toBeNull('Original currency amount must be stored');
        expect($line->original_currency_amount)->toBeInstanceOf(\Brick\Money\Money::class, 'Should be Money object');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD', 'Original currency should be USD');
        expect($line->original_currency_amount->isEqualTo($monthlyDepreciationUSD))->toBeTrue('Original amount should be $90 USD');

        // EXPECTED: Original currency ID should be stored
        expect($line->original_currency_id)->toBe($this->usd->id, 'Original currency ID should be USD');

        // EXPECTED: Exchange rate should be stored
        expect($line->exchange_rate_at_transaction)->toBe(1460.0, 'Exchange rate should be stored');
    }

    // Verify the debit line (depreciation expense)
    $debitLine = $journalEntry->lines->filter(fn($line) => $line->debit->isPositive())->first();
    expect($debitLine->account_id)->toBe($depreciationExpenseAccount->id);
    expect($debitLine->debit->isEqualTo($expectedAmountIQD))->toBeTrue('Depreciation expense debit should be converted to IQD');

    // Verify the credit line (accumulated depreciation)
    $creditLine = $journalEntry->lines->filter(fn($line) => $line->credit->isPositive())->first();
    expect($creditLine->account_id)->toBe($accumulatedDepreciationAccount->id);
    expect($creditLine->credit->isEqualTo($expectedAmountIQD))->toBeTrue('Accumulated depreciation credit should be converted to IQD');
});

test('depreciation entry for same currency asset handles original currency fields correctly', function () {
    // Setup: Company with IQD base currency, asset also in IQD
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup minimal required accounts and configuration
    $assetAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'fixed_assets']);
    $depreciationExpenseAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'depreciation']);
    $accumulatedDepreciationAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'fixed_assets']);

    $depreciationJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
    ]);

    $company->update(['default_depreciation_journal_id' => $depreciationJournal->id]);

    // Create asset in IQD (same as company currency)
    $asset = Asset::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $this->iqd->id, // Same currency as company
        'purchase_value' => Money::of(8760000, 'IQD'), // 8,760,000 IQD
        'salvage_value' => Money::of(876000, 'IQD'), // 876,000 IQD
        'asset_account_id' => $assetAccount->id,
        'depreciation_expense_account_id' => $depreciationExpenseAccount->id,
        'accumulated_depreciation_account_id' => $accumulatedDepreciationAccount->id,
        'purchase_date' => now(),
        'useful_life_years' => 5,
    ]);

    // Monthly depreciation = (8,760,000 - 876,000) / 60 = 131,400 IQD per month
    $monthlyDepreciationIQD = Money::of(131400, 'IQD');

    $depreciationEntry = DepreciationEntry::factory()->create([
        'asset_id' => $asset->id,
        'amount' => $monthlyDepreciationIQD, // Amount in same currency (IQD)
        'depreciation_date' => now()->addMonth(),
        'status' => DepreciationEntryStatus::Draft,
    ]);

    $action = app(CreateJournalEntryForDepreciationAction::class);
    $journalEntry = $action->execute($depreciationEntry, $user);

    // Verify same currency handling
    expect($journalEntry->currency->code)->toBe('IQD');

    foreach ($journalEntry->lines as $line) {
        // For same currency, original amount should equal the IQD amount as Money object
        expect($line->original_currency_amount)->toBeInstanceOf(\Brick\Money\Money::class, 'Should be Money object');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('IQD', 'Original currency should be IQD');
        expect($line->original_currency_amount->isEqualTo($monthlyDepreciationIQD))->toBeTrue('Original amount should be 131,400 IQD');
        expect($line->original_currency_id)->toBe($this->iqd->id, 'Original currency ID should be IQD');
        expect($line->exchange_rate_at_transaction)->toBe(1.0, 'Exchange rate should be 1.0 for same currency');
    }
});

test('depreciation entry correctly identifies asset original currency for conversion', function () {
    // This test verifies that depreciation uses the asset's original currency, not the depreciation entry currency
    $company = Company::factory()->create(['currency_id' => $this->iqd->id]);
    $user = User::factory()->create();

    // Setup minimal accounts
    $assetAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'fixed_assets']);
    $depreciationExpenseAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'depreciation']);
    $accumulatedDepreciationAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'fixed_assets']);

    $depreciationJournal = Journal::factory()->create(['company_id' => $company->id, 'currency_id' => $company->currency_id]);
    $company->update(['default_depreciation_journal_id' => $depreciationJournal->id]);

    // Create asset in EUR
    $eur = Currency::firstOrCreate(['code' => 'EUR'], [
        'name' => 'Euro',
        'symbol' => '€',
        'exchange_rate' => 1600.0, // 1 EUR = 1600 IQD
        'is_active' => true,
        'decimal_places' => 2
    ]);

    $asset = Asset::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $eur->id, // Asset in EUR
        'purchase_value' => Money::of(3600, 'EUR'), // €3,600 EUR
        'salvage_value' => Money::of(360, 'EUR'), // €360 EUR
        'asset_account_id' => $assetAccount->id,
        'depreciation_expense_account_id' => $depreciationExpenseAccount->id,
        'accumulated_depreciation_account_id' => $accumulatedDepreciationAccount->id,
        'purchase_date' => now(),
        'useful_life_years' => 5,
    ]);

    // Monthly depreciation = (€3,600 - €360) / 60 = €54 EUR per month
    $monthlyDepreciationEUR = Money::of(54, 'EUR');

    $depreciationEntry = DepreciationEntry::factory()->create([
        'asset_id' => $asset->id,
        'amount' => $monthlyDepreciationEUR,
        'depreciation_date' => now()->addMonth(),
        'status' => DepreciationEntryStatus::Draft,
    ]);

    $action = app(CreateJournalEntryForDepreciationAction::class);
    $journalEntry = $action->execute($depreciationEntry, $user);

    // Verify journal entry is in company base currency (IQD), not asset currency (EUR)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must always be in company base currency');

    // Calculate expected amount: €54 EUR * 1600 = 86,400 IQD
    $expectedAmountIQD = Money::of(86400, 'IQD');
    expect($journalEntry->total_debit->isEqualTo($expectedAmountIQD))->toBeTrue();

    // Verify original currency tracking
    foreach ($journalEntry->lines as $line) {
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('EUR');
        expect($line->original_currency_amount->isEqualTo($monthlyDepreciationEUR))->toBeTrue();
        expect($line->exchange_rate_at_transaction)->toBe(1600.0);
    }
});

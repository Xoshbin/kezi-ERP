<?php

namespace Tests\Feature\Actions\Accounting;

use App\Actions\Accounting\CreateJournalEntryForAssetAcquisitionAction;
use App\Models\Account;
use App\Models\Asset;
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

test('asset acquisition in foreign currency creates journal entry in base currency with original currency tracking', function () {
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

    $apAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Accounts Payable',
        'code' => '2100',
        'type' => 'payable'
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
        'default_accounts_payable_id' => $apAccount->id,
        'default_depreciation_journal_id' => $depreciationJournal->id,
    ]);

    // Create asset purchased in USD
    $asset = Asset::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $this->usd->id, // Asset purchased in USD
        'purchase_value' => Money::of(5000, 'USD'), // $5,000 USD
        'salvage_value' => Money::of(500, 'USD'), // $500 USD
        'asset_account_id' => $assetAccount->id,
        'depreciation_expense_account_id' => $depreciationExpenseAccount->id,
        'accumulated_depreciation_account_id' => $accumulatedDepreciationAccount->id,
        'purchase_date' => now(),
        'useful_life_years' => 5,
    ]);

    // Execute the action
    $action = app(CreateJournalEntryForAssetAcquisitionAction::class);
    $journalEntry = $action->execute($asset, $user);

    // Verify journal entry is created in base currency (IQD)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must be in company base currency');

    // Calculate expected amount in IQD: $5,000 USD * 1460 = 7,300,000 IQD
    $expectedAmountIQD = Money::of(7300000, 'IQD');
    expect($journalEntry->total_debit->isEqualTo($expectedAmountIQD))->toBeTrue('Total debit should be converted to IQD');
    expect($journalEntry->total_credit->isEqualTo($expectedAmountIQD))->toBeTrue('Total credit should be converted to IQD');

    // Verify journal entry lines store original currency information
    $journalEntry->load('lines');
    expect($journalEntry->lines)->toHaveCount(2); // Asset debit + AP credit

    foreach ($journalEntry->lines as $line) {
        // EXPECTED: Original currency amount should be stored as Money object in USD
        expect($line->original_currency_amount)->not->toBeNull('Original currency amount must be stored');
        expect($line->original_currency_amount)->toBeInstanceOf(\Brick\Money\Money::class, 'Should be Money object');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD', 'Original currency should be USD');
        expect($line->original_currency_amount->isEqualTo(Money::of(5000, 'USD')))->toBeTrue('Original amount should be $5,000 USD');

        // EXPECTED: Original currency ID should be stored
        expect($line->original_currency_id)->toBe($this->usd->id, 'Original currency ID should be USD');

        // EXPECTED: Exchange rate should be stored
        expect($line->exchange_rate_at_transaction)->toBe(1460.0, 'Exchange rate should be stored');
    }

    // Verify the debit line (asset)
    $debitLine = $journalEntry->lines->filter(fn($line) => $line->debit->isPositive())->first();
    expect($debitLine->account_id)->toBe($assetAccount->id);
    expect($debitLine->debit->isEqualTo($expectedAmountIQD))->toBeTrue('Asset debit should be converted to IQD');

    // Verify the credit line (AP)
    $creditLine = $journalEntry->lines->filter(fn($line) => $line->credit->isPositive())->first();
    expect($creditLine->account_id)->toBe($apAccount->id);
    expect($creditLine->credit->isEqualTo($expectedAmountIQD))->toBeTrue('AP credit should be converted to IQD');
});

test('asset acquisition in same currency as company base currency handles original currency fields correctly', function () {
    // Setup: Company with IQD base currency, asset also purchased in IQD
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup minimal required accounts and configuration
    $assetAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'fixed_assets']);
    $apAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'payable']);
    $depreciationExpenseAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'depreciation']);
    $accumulatedDepreciationAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'fixed_assets']);
    
    $depreciationJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
    ]);

    $company->update([
        'default_accounts_payable_id' => $apAccount->id,
        'default_depreciation_journal_id' => $depreciationJournal->id,
    ]);

    // Create asset purchased in IQD (same as company currency)
    $asset = Asset::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $this->iqd->id, // Same currency as company
        'purchase_value' => Money::of(7300000, 'IQD'), // 7,300,000 IQD
        'salvage_value' => Money::of(730000, 'IQD'), // 730,000 IQD
        'asset_account_id' => $assetAccount->id,
        'depreciation_expense_account_id' => $depreciationExpenseAccount->id,
        'accumulated_depreciation_account_id' => $accumulatedDepreciationAccount->id,
        'purchase_date' => now(),
        'useful_life_years' => 5,
    ]);

    $action = app(CreateJournalEntryForAssetAcquisitionAction::class);
    $journalEntry = $action->execute($asset, $user);

    // Verify same currency handling
    expect($journalEntry->currency->code)->toBe('IQD');
    
    foreach ($journalEntry->lines as $line) {
        // For same currency, original amount should equal the IQD amount as Money object
        expect($line->original_currency_amount)->toBeInstanceOf(\Brick\Money\Money::class, 'Should be Money object');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('IQD', 'Original currency should be IQD');
        expect($line->original_currency_amount->isEqualTo(Money::of(7300000, 'IQD')))->toBeTrue('Original amount should be 7,300,000 IQD');
        expect($line->original_currency_id)->toBe($this->iqd->id, 'Original currency ID should be IQD');
        expect($line->exchange_rate_at_transaction)->toBe(1.0, 'Exchange rate should be 1.0 for same currency');
    }
});

test('asset acquisition uses company base currency for journal entry regardless of asset currency', function () {
    // This test specifically verifies the currency conversion logic
    $company = Company::factory()->create(['currency_id' => $this->iqd->id]);
    $user = User::factory()->create();

    // Setup minimal accounts
    $assetAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'fixed_assets']);
    $apAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'payable']);
    $depreciationExpenseAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'depreciation']);
    $accumulatedDepreciationAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'fixed_assets']);
    
    $depreciationJournal = Journal::factory()->create(['company_id' => $company->id, 'currency_id' => $company->currency_id]);
    $company->update([
        'default_accounts_payable_id' => $apAccount->id,
        'default_depreciation_journal_id' => $depreciationJournal->id,
    ]);

    // Create asset in EUR (different from both base and USD)
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
        'purchase_value' => Money::of(3000, 'EUR'), // €3,000 EUR
        'asset_account_id' => $assetAccount->id,
        'depreciation_expense_account_id' => $depreciationExpenseAccount->id,
        'accumulated_depreciation_account_id' => $accumulatedDepreciationAccount->id,
        'purchase_date' => now(),
        'useful_life_years' => 5,
    ]);

    $action = app(CreateJournalEntryForAssetAcquisitionAction::class);
    $journalEntry = $action->execute($asset, $user);

    // Verify journal entry is in company base currency (IQD), not asset currency (EUR)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must always be in company base currency');

    // Calculate expected amount: €3,000 EUR * 1600 = 4,800,000 IQD
    $expectedAmountIQD = Money::of(4800000, 'IQD');
    expect($journalEntry->total_debit->isEqualTo($expectedAmountIQD))->toBeTrue();

    // Verify original currency tracking
    foreach ($journalEntry->lines as $line) {
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('EUR');
        expect($line->original_currency_amount->isEqualTo(Money::of(3000, 'EUR')))->toBeTrue();
        expect($line->exchange_rate_at_transaction)->toBe(1600.0);
    }
});

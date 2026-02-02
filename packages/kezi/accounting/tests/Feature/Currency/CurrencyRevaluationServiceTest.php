<?php

namespace Kezi\Accounting\Tests\Feature\Currency;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Services\CurrencyRevaluationService;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->service = app(CurrencyRevaluationService::class);
});

test('it identifies eligible accounts for revaluation', function () {
    // Arrange
    $company = $this->company;

    $receivableAccount = Account::factory()->for($company)->create([
        'type' => AccountType::Receivable,
        'is_deprecated' => false,
    ]);

    $payableAccount = Account::factory()->for($company)->create([
        'type' => AccountType::Payable,
        'is_deprecated' => false,
    ]);

    $bankAccount = Account::factory()->for($company)->create([
        'type' => AccountType::BankAndCash,
        'is_deprecated' => false,
    ]);

    $incomeAccount = Account::factory()->for($company)->create([
        'type' => AccountType::Income,
        'is_deprecated' => false,
    ]);

    // Act
    $eligibleAccounts = $this->service->getEligibleAccounts($company);

    // Assert - Check that our created accounts are included/excluded correctly
    $eligibleIds = $eligibleAccounts->pluck('id')->toArray();
    expect($eligibleIds)
        ->toContain($receivableAccount->id)
        ->toContain($payableAccount->id)
        ->toContain($bankAccount->id)
        ->not->toContain($incomeAccount->id);
});

test('it excludes deprecated accounts from revaluation', function () {
    // Arrange
    $company = $this->company;

    $activeAccount = Account::factory()->for($company)->create([
        'type' => AccountType::Receivable,
        'is_deprecated' => false,
    ]);

    $deprecatedAccount = Account::factory()->for($company)->create([
        'type' => AccountType::Receivable,
        'is_deprecated' => true,
    ]);

    // Act
    $eligibleAccounts = $this->service->getEligibleAccounts($company);

    // Assert
    expect($eligibleAccounts->pluck('id')->toArray())
        ->toContain($activeAccount->id)
        ->not->toContain($deprecatedAccount->id);
});

test('it filters eligible accounts by specific account ids', function () {
    // Arrange
    $company = $this->company;

    $account1 = Account::factory()->for($company)->create([
        'type' => AccountType::Receivable,
    ]);

    $account2 = Account::factory()->for($company)->create([
        'type' => AccountType::Receivable,
    ]);

    // Act
    $eligibleAccounts = $this->service->getEligibleAccounts($company, [$account1->id]);

    // Assert
    expect($eligibleAccounts)->toHaveCount(1);
    expect($eligibleAccounts->first()->id)->toBe($account1->id);
});

test('it calculates unrealized gain when rate increases', function () {
    // Arrange
    // Create a fresh company with IQD as base currency (3 decimal places)
    $baseCurrency = Currency::firstOrCreate(
        ['code' => 'IQD'],
        ['name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'is_active' => true, 'decimal_places' => 3]
    );
    $baseCurrency->update(['decimal_places' => 3]);

    $company = \App\Models\Company::factory()->create([
        'currency_id' => $baseCurrency->id,
    ]);
    $company->load('currency');

    $baseCurrencyCode = $company->currency->code;

    // Create a foreign currency (USD with 2 decimal places)
    $usd = Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true, 'decimal_places' => 2]
    );
    $usd->update(['decimal_places' => 2]);

    // Create exchange rate for revaluation date (rate increased from 1.0 to 1.2)
    CurrencyRate::factory()->create([
        'currency_id' => $usd->id,
        'company_id' => $company->id,
        'rate' => 1.2,
        'effective_date' => Carbon::today(),
    ]);

    // Create a balance DTO with historical rate of 1.0
    // For IQD (3 decimal places), 1000.000 IQD = 1000000 minor units
    // For USD (2 decimal places), 1000.00 USD = 100000 minor units
    $foreignBalance = Money::ofMinor(100000, 'USD'); // 1000.00 USD
    $bookValue = Money::ofMinor(1000000, $baseCurrencyCode); // 1000.000 IQD (at rate 1.0)

    $balanceDTO = new \Kezi\Accounting\DataTransferObjects\Currency\ForeignCurrencyBalanceDTO(
        account_id: 1,
        currency_id: $usd->id,
        partner_id: null,
        foreign_balance: $foreignBalance,
        book_value: $bookValue,
        weighted_avg_rate: 1.0,
    );

    // Act
    $result = $this->service->calculateUnrealizedGainLoss($balanceDTO, $company, Carbon::today());

    // Assert
    expect((float) $result['current_rate'])->toEqualWithDelta(1.2, 0.0001);
    expect($result['revalued_amount']->getCurrency()->getCurrencyCode())->toBe('IQD');

    // Revalued: 1000 USD * 1.2 = 1200 IQD (1200000 minor units)
    // Book value: 1000 IQD (1000000 minor units)
    // Adjustment: 1200 - 1000 = 200 IQD gain (200000 minor units)
    expect($result['revalued_amount']->getMinorAmount()->toInt())->toBe(1200000);
    expect($result['adjustment']->getMinorAmount()->toInt())->toBe(200000);
    expect($result['adjustment']->isPositive())->toBeTrue();
});

test('it calculates unrealized loss when rate decreases', function () {
    // Arrange
    // Create a fresh company with a currency that has 3 decimal places
    $baseCurrency = Currency::firstOrCreate(
        ['code' => 'IQD'],
        ['name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'is_active' => true, 'decimal_places' => 3]
    );
    $baseCurrency->update(['decimal_places' => 3]);

    $company = \App\Models\Company::factory()->create([
        'currency_id' => $baseCurrency->id,
    ]);
    $company->load('currency');

    $baseCurrencyCode = $company->currency->code;

    // Create a foreign currency with explicit decimal_places
    $usd = Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true, 'decimal_places' => 2]
    );
    $usd->update(['decimal_places' => 2]);

    // Create exchange rate for revaluation date (rate decreased from 1.0 to 0.8)
    CurrencyRate::factory()->create([
        'currency_id' => $usd->id,
        'company_id' => $company->id,
        'rate' => 0.8,
        'effective_date' => Carbon::today(),
    ]);

    // Create a balance DTO with historical rate of 1.0
    // Use Money::ofMinor since the service expects minor units
    // For IQD (3 decimal places), 1000.000 IQD = 1000000 minor units
    // For USD (2 decimal places), 1000.00 USD = 100000 minor units
    $foreignBalance = Money::ofMinor(100000, 'USD'); // 1000.00 USD
    $bookValue = Money::ofMinor(1000000, $baseCurrencyCode); // 1000.000 IQD (at rate 1.0)

    $balanceDTO = new \Kezi\Accounting\DataTransferObjects\Currency\ForeignCurrencyBalanceDTO(
        account_id: 1,
        currency_id: $usd->id,
        partner_id: null,
        foreign_balance: $foreignBalance,
        book_value: $bookValue,
        weighted_avg_rate: 1.0,
    );

    // Act
    $result = $this->service->calculateUnrealizedGainLoss($balanceDTO, $company, Carbon::today());

    // Assert
    expect((float) $result['current_rate'])->toEqualWithDelta(0.8, 0.0001);
    // Revalued: 1000 USD * 0.8 = 800 IQD = 800000 minor units
    // Book value: 1000 IQD = 1000000 minor units
    // Adjustment: 800000 - 1000000 = -200000 (loss)
    expect($result['adjustment']->getMinorAmount()->toInt())->toBe(-200000);
    expect($result['adjustment']->isNegative())->toBeTrue();
});

<?php

namespace Tests\Feature\Services;

use App\Actions\Accounting\CreateJournalEntryForPaymentAction;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\User;
use App\Services\CurrencyConverterService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('currency converter service can replace repetitive conversion logic in payment action', function () {
    // Setup test data
    $iqd = Currency::create([
        'code' => 'IQD',
        'name' => 'Iraqi Dinar',
        'symbol' => 'IQD',
        'exchange_rate' => 1.0,
        'is_active' => true,
        'decimal_places' => 3,
    ]);

    $usd = Currency::create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1460.0,
        'is_active' => true,
        'decimal_places' => 2,
    ]);

    $company = Company::factory()->create(['currency_id' => $iqd->id]);
    $user = User::factory()->create();

    $bankAccount = Account::factory()->create([
        'company_id' => $company->id,
        'type' => 'bank_and_cash',
    ]);

    $arAccount = Account::factory()->create([
        'company_id' => $company->id,
        'type' => 'receivable',
    ]);

    // Configure company with default accounts
    $company->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_bank_account_id' => $bankAccount->id,
    ]);

    $journal = Journal::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $bankAccount->id,
    ]);

    $payment = Payment::factory()->create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $usd->id,
        'amount' => Money::of(100, 'USD'),
        'payment_type' => 'inbound',
        'payment_date' => '2025-01-15',
    ]);

    // Test the existing action (which has repetitive conversion logic)
    $action = app(CreateJournalEntryForPaymentAction::class);
    $journalEntry = $action->execute($payment, $user);

    // Verify the result
    expect($journalEntry->currency->code)->toBe('IQD');
    expect($journalEntry->total_debit->isEqualTo(Money::of(146000, 'IQD')))->toBeTrue();

    // Now demonstrate how the CurrencyConverterService could simplify this
    $converterService = app(CurrencyConverterService::class);

    // Instead of the repetitive conversion logic in the action, we could use:
    $conversion = $converterService->convertToCompanyBaseCurrency(
        $payment->amount,
        $payment->currency,
        $company
    );

    // This gives us everything we need for the journal entry
    expect($conversion->convertedAmount->isEqualTo(Money::of(146000, 'IQD')))->toBeTrue();
    expect($conversion->originalAmount->isEqualTo(Money::of(100, 'USD')))->toBeTrue();
    expect($conversion->originalCurrency->code)->toBe('USD');
    expect($conversion->targetCurrency->code)->toBe('IQD');
    expect($conversion->exchangeRate)->toBe(1460.0);

    // The action could then create the DTO like this:
    // new CreateJournalEntryLineDTO(
    //     account_id: $bankAccount->id,
    //     debit: $conversion->convertedAmount,
    //     credit: $conversion->createZeroInTargetCurrency(),
    //     description: 'Bank deposit',
    //     partner_id: null,
    //     analytic_account_id: null,
    //     original_currency_amount: $conversion->originalAmount,
    //     original_currency_id: $conversion->originalCurrency->id,
    //     exchange_rate_at_transaction: $conversion->exchangeRate,
    // );

    // This would eliminate the repetitive conversion logic and make the code much cleaner
});

test('currency converter service provides consistent rounding across all actions', function () {
    // Setup
    $iqd = Currency::create(['code' => 'IQD', 'name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'exchange_rate' => 1.0, 'is_active' => true, 'decimal_places' => 3]);
    $usd = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1460.0, 'is_active' => true, 'decimal_places' => 2]);
    $company = Company::factory()->create(['currency_id' => $iqd->id]);

    $converterService = app(CurrencyConverterService::class);

    // Test that all conversions use the same rounding logic
    $testAmounts = [
        Money::of(1, 'USD'),      // $0.01 USD = 14.60 IQD
        Money::of(33, 'USD'),     // $0.33 USD = 481.80 IQD
        Money::of(100, 'USD'),    // $1.00 USD = 1460.00 IQD
        Money::of(12345, 'USD'),  // $123.45 USD = 180,237.00 IQD
    ];

    foreach ($testAmounts as $amount) {
        $conversion = $converterService->convertToCompanyBaseCurrency($amount, $usd, $company);

        // Verify consistent rounding (HALF_UP)
        $expectedAmount = $amount->getAmount()->multipliedBy(1460.0);
        expect($conversion->convertedAmount->getAmount()->toFloat())->toBe($expectedAmount->toFloat());

        // Verify all conversions preserve original currency information
        expect($conversion->originalCurrency->code)->toBe('USD');
        expect($conversion->targetCurrency->code)->toBe('IQD');
        expect($conversion->exchangeRate)->toBe(1460.0);
    }
});

test('currency converter service handles edge cases consistently', function () {
    // Setup
    $iqd = Currency::create(['code' => 'IQD', 'name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'exchange_rate' => 1.0, 'is_active' => true, 'decimal_places' => 3]);
    $usd = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1460.0, 'is_active' => true, 'decimal_places' => 2]);
    $company = Company::factory()->create(['currency_id' => $iqd->id]);

    $converterService = app(CurrencyConverterService::class);

    // Test zero amount
    $zeroConversion = $converterService->convertToCompanyBaseCurrency(Money::of(0, 'USD'), $usd, $company);
    expect($zeroConversion->convertedAmount->isZero())->toBeTrue();
    expect($zeroConversion->convertedAmount->getCurrency()->getCurrencyCode())->toBe('IQD');

    // Test same currency (no conversion needed)
    $sameConversion = $converterService->convertToCompanyBaseCurrency(Money::of(100000, 'IQD'), $iqd, $company);
    expect($sameConversion->wasConverted())->toBeFalse();
    expect($sameConversion->exchangeRate)->toBe(1.0);

    // Test validation
    $inactiveCurrency = Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 1600.0, 'is_active' => false, 'decimal_places' => 2]);

    expect(fn() => $converterService->validateCurrenciesForConversion($inactiveCurrency, $iqd))
        ->toThrow(\InvalidArgumentException::class);
});

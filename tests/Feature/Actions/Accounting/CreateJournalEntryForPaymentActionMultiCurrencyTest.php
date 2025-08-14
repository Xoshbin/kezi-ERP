<?php

namespace Tests\Feature\Actions\Accounting;

use App\Actions\Accounting\CreateJournalEntryForPaymentAction;
use App\Enums\Payments\PaymentType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
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

test('inbound payment in foreign currency creates journal entry in base currency with original currency tracking', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup accounts
    $bankAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank Account',
        'code' => '1100',
        'type' => 'bank_and_cash'
    ]);

    $arAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Accounts Receivable',
        'code' => '1200',
        'type' => 'receivable'
    ]);

    // Create journal
    $bankJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank Journal',
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $bankAccount->id,
    ]);

    // Configure company
    $company->update([
        'default_accounts_receivable_id' => $arAccount->id,
    ]);

    // Create customer
    $customer = Partner::factory()->customer()->create([
        'company_id' => $company->id,
        'name' => 'USD Customer'
    ]);

    // Create inbound payment in USD
    $payment = Payment::factory()->create([
        'company_id' => $company->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->usd->id, // Payment in USD
        'amount' => Money::of(100, 'USD'), // $100 USD
        'payment_type' => PaymentType::Inbound,
        'payment_date' => now()->format('Y-m-d'),
    ]);

    // Execute the action
    $action = app(CreateJournalEntryForPaymentAction::class);
    $journalEntry = $action->execute($payment, $user);

    // Verify journal entry is created in base currency (IQD)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must be in company base currency');

    // Calculate expected amount in IQD: $100 USD * 1460 = 146,000 IQD
    $expectedAmountIQD = Money::of(146000, 'IQD');
    expect($journalEntry->total_debit->isEqualTo($expectedAmountIQD))->toBeTrue('Total debit should be converted to IQD');
    expect($journalEntry->total_credit->isEqualTo($expectedAmountIQD))->toBeTrue('Total credit should be converted to IQD');

    // Verify journal entry lines store original currency information
    $journalEntry->load('lines');
    expect($journalEntry->lines)->toHaveCount(2); // Bank debit + AR credit

    foreach ($journalEntry->lines as $line) {
        // EXPECTED: Original currency amount should be stored as Money object in USD
        expect($line->original_currency_amount)->not->toBeNull('Original currency amount must be stored');
        expect($line->original_currency_amount)->toBeInstanceOf(\Brick\Money\Money::class, 'Should be Money object');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('USD', 'Original currency should be USD');
        expect($line->original_currency_amount->isEqualTo(Money::of(100, 'USD')))->toBeTrue('Original amount should be $100 USD');

        // EXPECTED: Original currency ID should be stored
        expect($line->original_currency_id)->toBe($this->usd->id, 'Original currency ID should be USD');

        // EXPECTED: Exchange rate should be stored
        expect($line->exchange_rate_at_transaction)->toBe(1460.0, 'Exchange rate should be stored');
    }

    // Verify the debit line (bank)
    $debitLine = $journalEntry->lines->filter(fn($line) => $line->debit->isPositive())->first();
    expect($debitLine->account_id)->toBe($bankAccount->id);
    expect($debitLine->debit->isEqualTo($expectedAmountIQD))->toBeTrue('Bank debit should be converted to IQD');

    // Verify the credit line (AR)
    $creditLine = $journalEntry->lines->filter(fn($line) => $line->credit->isPositive())->first();
    expect($creditLine->account_id)->toBe($arAccount->id);
    expect($creditLine->credit->isEqualTo($expectedAmountIQD))->toBeTrue('AR credit should be converted to IQD');
});

test('outbound payment in foreign currency creates journal entry in base currency with original currency tracking', function () {
    // Setup: Company with IQD base currency
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup accounts
    $bankAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank Account',
        'code' => '1100',
        'type' => 'bank_and_cash'
    ]);

    $apAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Accounts Payable',
        'code' => '2100',
        'type' => 'payable'
    ]);

    // Create journal
    $bankJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank Journal',
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $bankAccount->id,
    ]);

    // Configure company
    $company->update([
        'default_accounts_payable_id' => $apAccount->id,
    ]);

    // Create vendor
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $company->id,
        'name' => 'USD Vendor'
    ]);

    // Create outbound payment in USD
    $payment = Payment::factory()->create([
        'company_id' => $company->id,
        'paid_to_from_partner_id' => $vendor->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->usd->id, // Payment in USD
        'amount' => Money::of(50, 'USD'), // $50 USD
        'payment_type' => PaymentType::Outbound,
        'payment_date' => now()->format('Y-m-d'),
    ]);

    // Execute the action
    $action = app(CreateJournalEntryForPaymentAction::class);
    $journalEntry = $action->execute($payment, $user);

    // Verify journal entry is created in base currency (IQD)
    expect($journalEntry->currency->code)->toBe('IQD', 'Journal entry must be in company base currency');

    // Calculate expected amount in IQD: $50 USD * 1460 = 73,000 IQD
    $expectedAmountIQD = Money::of(73000, 'IQD');
    expect($journalEntry->total_debit->isEqualTo($expectedAmountIQD))->toBeTrue('Total debit should be converted to IQD');
    expect($journalEntry->total_credit->isEqualTo($expectedAmountIQD))->toBeTrue('Total credit should be converted to IQD');

    // Verify original currency tracking
    $journalEntry->load('lines');
    foreach ($journalEntry->lines as $line) {
        expect($line->original_currency_amount->isEqualTo(Money::of(50, 'USD')))->toBeTrue('Original amount should be $50 USD');
        expect($line->original_currency_id)->toBe($this->usd->id, 'Original currency ID should be USD');
        expect($line->exchange_rate_at_transaction)->toBe(1460.0, 'Exchange rate should be stored');
    }

    // Verify the debit line (AP)
    $debitLine = $journalEntry->lines->filter(fn($line) => $line->debit->isPositive())->first();
    expect($debitLine->account_id)->toBe($apAccount->id);

    // Verify the credit line (bank)
    $creditLine = $journalEntry->lines->filter(fn($line) => $line->credit->isPositive())->first();
    expect($creditLine->account_id)->toBe($bankAccount->id);
});

test('payment in same currency as company base currency handles original currency fields correctly', function () {
    // Setup: Company with IQD base currency, payment also in IQD
    $company = Company::factory()->create([
        'currency_id' => $this->iqd->id,
        'name' => 'Test Company IQD'
    ]);

    $user = User::factory()->create();

    // Setup minimal required accounts and configuration
    $bankAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'bank_and_cash']);
    $arAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'receivable']);

    $bankJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $bankAccount->id,
    ]);

    $company->update(['default_accounts_receivable_id' => $arAccount->id]);

    $customer = Partner::factory()->customer()->create(['company_id' => $company->id]);

    // Create payment in IQD (same as company currency)
    $payment = Payment::factory()->create([
        'company_id' => $company->id,
        'paid_to_from_partner_id' => $customer->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->iqd->id, // Same currency as company
        'amount' => Money::of(73000, 'IQD'), // 73,000 IQD
        'payment_type' => PaymentType::Inbound,
        'payment_date' => now()->format('Y-m-d'),
    ]);

    $action = app(CreateJournalEntryForPaymentAction::class);
    $journalEntry = $action->execute($payment, $user);

    // Verify same currency handling
    expect($journalEntry->currency->code)->toBe('IQD');

    foreach ($journalEntry->lines as $line) {
        // For same currency, original amount should equal the IQD amount as Money object
        expect($line->original_currency_amount)->toBeInstanceOf(\Brick\Money\Money::class, 'Should be Money object');
        expect($line->original_currency_amount->getCurrency()->getCurrencyCode())->toBe('IQD', 'Original currency should be IQD');
        expect($line->original_currency_amount->isEqualTo(Money::of(73000, 'IQD')))->toBeTrue('Original amount should be 73,000 IQD');
        expect($line->original_currency_id)->toBe($this->iqd->id, 'Original currency ID should be IQD');
        expect($line->exchange_rate_at_transaction)->toBe(1.0, 'Exchange rate should be 1.0 for same currency');
    }
});

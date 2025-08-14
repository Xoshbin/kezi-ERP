<?php

namespace Tests\Feature\Services\Reports;

use App\Actions\Accounting\CreateJournalEntryForPaymentAction;
use App\Enums\Payments\PaymentType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\User;
use App\Services\Reports\TrialBalanceService;
use Brick\Money\Money;
use Carbon\Carbon;
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

test('trial balance report correctly shows multi-currency transactions in base currency', function () {
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

    // Create bank journal
    $bankJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank Journal',
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $bankAccount->id,
    ]);

    // Configure company
    $company->update([
        'default_accounts_receivable_id' => $arAccount->id,
        'default_bank_journal_id' => $bankJournal->id,
    ]);

    // Transaction 1: Create inbound payment in USD ($100 USD = 146,000 IQD)
    $payment = Payment::factory()->create([
        'company_id' => $company->id,
        'journal_id' => $bankJournal->id,
        'currency_id' => $this->usd->id,
        'amount' => Money::of(100, 'USD'),
        'payment_type' => PaymentType::Inbound,
        'payment_date' => '2025-01-20',
    ]);

    // Create journal entry for payment
    $paymentAction = app(CreateJournalEntryForPaymentAction::class);
    $paymentAction->execute($payment, $user);

    // Generate trial balance report
    $trialBalanceService = app(TrialBalanceService::class);
    $asOfDate = Carbon::parse('2025-01-31');
    $trialBalance = $trialBalanceService->generate($company, $asOfDate);

    // Verify that all amounts are in company base currency (IQD)
    expect($trialBalance->totalDebit->getCurrency()->getCurrencyCode())->toBe('IQD');
    expect($trialBalance->isBalanced)->toBeTrue();

    // Expected amounts in IQD (converted from USD at 1460 rate)
    $expectedAmount = Money::of(146000, 'IQD'); // $100 USD * 1460

    // Find account lines in the trial balance
    $bankLine = $trialBalance->reportLines->firstWhere('accountId', $bankAccount->id);
    $arLine = $trialBalance->reportLines->firstWhere('accountId', $arAccount->id);

    // For inbound payment: Bank account debited, AR account credited
    expect($bankLine->debit->isEqualTo($expectedAmount))->toBeTrue('Bank account should show debit of 146,000 IQD');
    expect($arLine->credit->isEqualTo($expectedAmount))->toBeTrue('AR account should show credit of 146,000 IQD');

    // Verify totals
    expect($trialBalance->totalDebit->isEqualTo($expectedAmount))->toBeTrue();
    expect($trialBalance->totalCredit->isEqualTo($expectedAmount))->toBeTrue();
});



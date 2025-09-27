<?php

use App\Exceptions\Reconciliation\ReconciliationDisabledException;
use App\Models\Account;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\User;
use App\Services\BankReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(\Modules\Accounting\Services\BankReconciliationService::class);
    $this->user = User::factory()->create();
});

test('reconcilePayment throws exception when reconciliation is disabled', function () {
    // Create company with reconciliation disabled
    $company = Company::factory()->create(['enable_reconciliation' => false]);

    // Create an account for the journal
    $account = \Modules\Accounting\Models\Account::create([
        'company_id' => $company->id,
        'currency_id' => $company->currency_id,
        'code' => '1000',
        'name' => 'Test Account',
        'type' => 'bank_and_cash',
        'is_deprecated' => false,
        'allow_reconciliation' => false,
    ]);

    // Create a journal for the company
    $journal = Journal::create([
        'company_id' => $company->id,
        'name' => 'Test Journal',
        'type' => 'bank',
        'short_code' => 'TST',
        'currency_id' => $company->currency_id,
        'default_debit_account_id' => $account->id,
        'default_credit_account_id' => $account->id,
    ]);

    // Create a partner for the payment
    $partner = \Modules\Foundation\Models\Partner::create([
        'company_id' => $company->id,
        'name' => 'Test Partner',
        'type' => 'customer',
        'is_customer' => true,
        'is_vendor' => false,
    ]);

    // Create payment manually to ensure correct company relationship
    $payment = \Modules\Payment\Models\Payment::create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $company->currency_id,
        'paid_to_from_partner_id' => $partner->id,
        'payment_date' => now(),
        'amount' => 100000, // Money in minor units
        'payment_type' => 'inbound',
        'status' => 'confirmed',
        'reference' => 'TEST-001',
        'description' => 'Test payment',
    ]);

    // Create bank statement and line manually
    $bankStatement = \Modules\Accounting\Models\BankStatement::create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $company->currency_id,
        'reference' => 'STMT-001',
        'starting_balance' => 0,
        'ending_balance' => 100000,
        'date' => now(),
    ]);

    $statementLine = \Modules\Accounting\Models\BankStatementLine::create([
        'company_id' => $company->id,
        'bank_statement_id' => $bankStatement->id,
        'amount' => 100000,
        'description' => 'Test transaction',
        'date' => now(),
        'is_reconciled' => false,
    ]);

    expect(fn () => $this->service->reconcilePayment($payment, $statementLine, $this->user))
        ->toThrow(ReconciliationDisabledException::class);
});

test('reconcilePayment succeeds when reconciliation is enabled', function () {
    // Create company with reconciliation enabled and required accounts
    $company = \Tests\Builders\CompanyBuilder::new()
        ->withDefaultAccounts()
        ->withReconciliationEnabled()
        ->create();

    // Create payment and bank statement line
    $payment = \Modules\Payment\Models\Payment::factory()->for($company)->create([
        'currency_id' => $company->currency_id,
        'status' => \App\Enums\Payments\PaymentStatus::Confirmed,
    ]);
    $bankStatement = \Modules\Accounting\Models\BankStatement::factory()->for($company)->create([
        'currency_id' => $company->currency_id,
    ]);
    $statementLine = \Modules\Accounting\Models\BankStatementLine::factory()->for($bankStatement)->create([
        'company_id' => $company->id,
        'is_reconciled' => false,
    ]);

    $this->service->reconcilePayment($payment, $statementLine, $this->user);

    expect($payment->fresh()->status)->toBe(\App\Enums\Payments\PaymentStatus::Reconciled)
        ->and($statementLine->fresh()->is_reconciled)->toBeTrue()
        ->and($statementLine->fresh()->payment_id)->toBe($payment->id);
});

test('reconcile throws exception when reconciliation is disabled for payments', function () {
    // Create company with reconciliation disabled
    $company = Company::factory()->create(['enable_reconciliation' => false]);

    // Create payments
    $payments = \Modules\Payment\Models\Payment::factory()->for($company)->count(2)->create();

    expect(fn () => $this->service->reconcile([], $payments->pluck('id')->toArray(), $this->user))
        ->toThrow(ReconciliationDisabledException::class);
});

test('reconcile throws exception when reconciliation is disabled for bank statement lines', function () {
    // Create company with reconciliation disabled
    $company = Company::factory()->create(['enable_reconciliation' => false]);

    // Create bank statement lines
    $bankStatement = \Modules\Accounting\Models\BankStatement::factory()->for($company)->create();
    $lines = \Modules\Accounting\Models\BankStatementLine::factory()->for($bankStatement)->count(2)->create();

    expect(fn () => $this->service->reconcile($lines->pluck('id')->toArray(), [], $this->user))
        ->toThrow(ReconciliationDisabledException::class);
});

test('reconcile succeeds when reconciliation is enabled', function () {
    // Create company with reconciliation enabled and required accounts
    $company = \Tests\Builders\CompanyBuilder::new()
        ->withDefaultAccounts()
        ->withReconciliationEnabled()
        ->create();

    // Create payment and bank statement line with matching amounts
    $amount = \Brick\Money\Money::of(100, $company->currency->code);

    $payment = \Modules\Payment\Models\Payment::factory()->for($company)->create([
        'currency_id' => $company->currency_id,
        'amount' => $amount->getMinorAmount()->toInt(),
        'status' => \App\Enums\Payments\PaymentStatus::Confirmed,
    ]);

    $bankStatement = \Modules\Accounting\Models\BankStatement::factory()->for($company)->create([
        'currency_id' => $company->currency_id,
    ]);
    $statementLine = \Modules\Accounting\Models\BankStatementLine::factory()->for($bankStatement)->create([
        'company_id' => $company->id,
        'amount' => $amount->getMinorAmount()->toInt(),
        'is_reconciled' => false,
    ]);

    $this->service->reconcile(
        [$statementLine->id],
        [$payment->id],
        $this->user
    );

    expect($payment->fresh()->status)->toBe(\App\Enums\Payments\PaymentStatus::Reconciled)
        ->and($statementLine->fresh()->is_reconciled)->toBeTrue();
});

test('reconcileMultiple throws exception when reconciliation is disabled for payments', function () {
    // Create company with reconciliation disabled
    $company = Company::factory()->create(['enable_reconciliation' => false]);

    // Create payments
    $payments = \Modules\Payment\Models\Payment::factory()->for($company)->count(2)->create();

    expect(fn () => $this->service->reconcileMultiple([], $payments->pluck('id')->toArray(), $this->user))
        ->toThrow(ReconciliationDisabledException::class);
});

test('reconcileMultiple throws exception when reconciliation is disabled for bank lines', function () {
    // Create company with reconciliation disabled
    $company = Company::factory()->create(['enable_reconciliation' => false]);

    // Create bank statement lines
    $bankStatement = \Modules\Accounting\Models\BankStatement::factory()->for($company)->create();
    $lines = \Modules\Accounting\Models\BankStatementLine::factory()->for($bankStatement)->count(2)->create();

    expect(fn () => $this->service->reconcileMultiple($lines->pluck('id')->toArray(), [], $this->user))
        ->toThrow(ReconciliationDisabledException::class);
});

test('reconcileMultiple succeeds when reconciliation is enabled', function () {
    // Create company with reconciliation enabled and required accounts
    $company = \Tests\Builders\CompanyBuilder::new()
        ->withDefaultAccounts()
        ->withReconciliationEnabled()
        ->create();

    // Create payments and bank statement lines with matching total amounts
    $amount1 = \Brick\Money\Money::of(100, $company->currency->code);
    $amount2 = \Brick\Money\Money::of(50, $company->currency->code);

    $payments = collect([
        \Modules\Payment\Models\Payment::factory()->for($company)->create([
            'currency_id' => $company->currency_id,
            'amount' => $amount1,
            'payment_type' => \App\Enums\Payments\PaymentType::Inbound,
            'status' => \App\Enums\Payments\PaymentStatus::Confirmed,
        ]),
        \Modules\Payment\Models\Payment::factory()->for($company)->create([
            'currency_id' => $company->currency_id,
            'amount' => $amount2,
            'payment_type' => \App\Enums\Payments\PaymentType::Inbound,
            'status' => \App\Enums\Payments\PaymentStatus::Confirmed,
        ]),
    ]);

    $bankStatement = \Modules\Accounting\Models\BankStatement::factory()->for($company)->create([
        'currency_id' => $company->currency_id,
    ]);
    $lines = collect([
        \Modules\Accounting\Models\BankStatementLine::factory()->for($bankStatement)->create([
            'company_id' => $company->id,
            'amount' => $amount1,
            'is_reconciled' => false,
        ]),
        \Modules\Accounting\Models\BankStatementLine::factory()->for($bankStatement)->create([
            'company_id' => $company->id,
            'amount' => $amount2,
            'is_reconciled' => false,
        ]),
    ]);

    $this->service->reconcileMultiple(
        $lines->pluck('id')->toArray(),
        $payments->pluck('id')->toArray(),
        $this->user
    );

    // Check that all payments are reconciled
    $payments->each(function ($payment) {
        expect($payment->fresh()->status)->toBe(\App\Enums\Payments\PaymentStatus::Reconciled);
    });

    // Check that all lines are reconciled
    $lines->each(function ($line) {
        expect($line->fresh()->is_reconciled)->toBeTrue();
    });
});

test('getUnreconciledBankLines returns only unreconciled lines', function () {
    // Create company (reconciliation setting doesn't affect this query method)
    $company = \Tests\Builders\CompanyBuilder::new()
        ->withDefaultAccounts()
        ->withReconciliationEnabled()
        ->create();

    $bankStatement = \Modules\Accounting\Models\BankStatement::factory()->for($company)->create();

    // Create reconciled and unreconciled lines
    \Modules\Accounting\Models\BankStatementLine::factory()->for($bankStatement)->create(['is_reconciled' => true]);
    $unreconciledLine = \Modules\Accounting\Models\BankStatementLine::factory()->for($bankStatement)->create(['is_reconciled' => false]);

    $result = $this->service->getUnreconciledBankLines($bankStatement->id);

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($unreconciledLine->id);
});

test('getUnreconciledPayments returns only unreconciled payments', function () {
    // Create company (reconciliation setting doesn't affect this query method)
    $company = \Tests\Builders\CompanyBuilder::new()
        ->withDefaultAccounts()
        ->withReconciliationEnabled()
        ->create();

    // Create confirmed and reconciled payments
    $confirmedPayment = \Modules\Payment\Models\Payment::factory()->for($company)->create([
        'status' => \App\Enums\Payments\PaymentStatus::Confirmed,
    ]);
    \Modules\Payment\Models\Payment::factory()->for($company)->create([
        'status' => \App\Enums\Payments\PaymentStatus::Reconciled,
    ]);

    $result = $this->service->getUnreconciledPayments($company->id);

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($confirmedPayment->id);
});

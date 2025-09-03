<?php

use App\Enums\Accounting\JournalType;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Models\Account;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Journal;
use App\Models\Payment;
use App\Services\BankReconciliationService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->bankJournal = Journal::factory()
        ->for($this->company)
        ->create(['type' => JournalType::Bank]);

    $this->bankStatement = BankStatement::factory()
        ->for($this->company)
        ->for($this->company->currency)
        ->for($this->bankJournal)
        ->create();

    $this->service = app(BankReconciliationService::class);
});

describe('BankReconciliationService', function () {
    it('can reconcile multiple bank lines with multiple payments', function () {
        // Create bank statement lines
        $bankLine1 = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->company->currency->code),
                'is_reconciled' => false,
            ]);

        $bankLine2 = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(50, $this->company->currency->code),
                'is_reconciled' => false,
            ]);

        // Create payments
        $payment1 = Payment::factory()
            ->for($this->company)
            ->for($this->company->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, $this->company->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        $payment2 = Payment::factory()
            ->for($this->company)
            ->for($this->company->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(50, $this->company->currency->code),
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        // Reconcile
        $this->service->reconcileMultiple(
            [$bankLine1->id, $bankLine2->id],
            [$payment1->id, $payment2->id],
            $this->user
        );

        // Assert bank lines are reconciled
        expect($bankLine1->fresh()->is_reconciled)->toBeTrue();
        expect($bankLine2->fresh()->is_reconciled)->toBeTrue();

        // Assert payments are reconciled
        expect($payment1->fresh()->status)->toBe(PaymentStatus::Reconciled);
        expect($payment2->fresh()->status)->toBe(PaymentStatus::Reconciled);

        // Assert first bank line is linked to first payment
        expect($bankLine1->fresh()->payment_id)->toBe($payment1->id);
    });

    it('throws exception when totals do not match', function () {
        $bankLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(100, $this->company->currency->code),
                'is_reconciled' => false,
            ]);

        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->company->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(50, $this->company->currency->code), // Different amount
                'payment_type' => 'inbound',
                'status' => 'confirmed',
            ]);

        expect(fn () => $this->service->reconcileMultiple(
            [$bankLine->id],
            [$payment->id],
            $this->user
        ))->toThrow(RuntimeException::class, 'Bank statement lines total does not match payments total');
    });

    it('handles outbound payments correctly in reconciliation', function () {
        $bankLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(-100, $this->company->currency->code), // Negative for outbound
                'is_reconciled' => false,
            ]);

        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->company->currency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, $this->company->currency->code),
                'payment_type' => 'outbound', // Outbound payment
                'status' => 'confirmed',
            ]);

        $this->service->reconcileMultiple(
            [$bankLine->id],
            [$payment->id],
            $this->user
        );

        expect($bankLine->fresh()->is_reconciled)->toBeTrue();
        expect($payment->fresh()->status)->toBe(PaymentStatus::Reconciled);
    });

    it('can create write-offs for bank statement lines', function () {
        $bankLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create([
                'amount' => Money::of(10, $this->company->currency->code),
                'is_reconciled' => false,
            ]);

        $writeOffAccount = Account::factory()
            ->for($this->company)
            ->create(['type' => 'expense']);

        $this->service->createWriteOff(
            $bankLine,
            $writeOffAccount,
            $this->user,
            'Small discrepancy write-off'
        );

        expect($bankLine->fresh()->is_reconciled)->toBeTrue();
    });

    it('can get unreconciled bank lines', function () {
        BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create(['is_reconciled' => true]);

        $unreconciledLine = BankStatementLine::factory()
            ->for($this->bankStatement)
            ->create(['is_reconciled' => false]);

        $result = $this->service->getUnreconciledBankLines($this->bankStatement->id);

        expect($result)->toHaveCount(1);
        expect($result->first()->id)->toBe($unreconciledLine->id);
    });

    it('can get unreconciled payments', function () {
        Payment::factory()
            ->for($this->company)
            ->for($this->company->currency)
            ->for($this->bankJournal)
            ->create(['status' => PaymentStatus::Reconciled]);

        $unreconciledPayment = Payment::factory()
            ->for($this->company)
            ->for($this->company->currency)
            ->for($this->bankJournal)
            ->create(['status' => PaymentStatus::Confirmed]);

        $result = $this->service->getUnreconciledPayments($this->company->id);

        expect($result)->toHaveCount(1);
        expect($result->first()->id)->toBe($unreconciledPayment->id);
    });
});

describe('Multi-Currency Bank Reconciliation', function () {
    beforeEach(function () {
        // Create USD currency for foreign currency tests
        $this->usdCurrency = Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => ['en' => 'US Dollar', 'ckb' => 'دۆلاری ئەمریکی', 'ar' => 'دولار أمريكي'],
                'symbol' => '$',
                'is_active' => true,
                'decimal_places' => 2,
            ]
        );

        // Set up exchange rate: 1 USD = 1460 IQD
        $this->exchangeRate = 1460.0;
        $this->transactionDate = Carbon::parse('2024-01-01');

        CurrencyRate::updateOrCreate(
            [
                'currency_id' => $this->usdCurrency->id,
                'effective_date' => $this->transactionDate->toDateString(),
                'company_id' => $this->company->id,
            ],
            [
                'rate' => $this->exchangeRate,
                'source' => 'manual',
            ]
        );

        // Create USD bank statement
        $this->usdBankStatement = BankStatement::factory()
            ->for($this->company)
            ->for($this->usdCurrency)
            ->for($this->bankJournal)
            ->create([
                'date' => $this->transactionDate,
                'starting_balance' => Money::of(0, 'USD'),
                'ending_balance' => Money::of(100, 'USD'),
            ]);
    });

    it('can reconcile same-currency transactions (USD statement + USD payment)', function () {
        // Create USD bank statement line
        $bankLine = BankStatementLine::factory()
            ->for($this->usdBankStatement)
            ->create([
                'amount' => Money::of(100, 'USD'), // $100.00
                'is_reconciled' => false,
                'date' => $this->transactionDate,
            ]);

        // Create USD payment
        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->usdCurrency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, 'USD'), // $100.00
                'payment_type' => PaymentType::Inbound,
                'status' => PaymentStatus::Confirmed,
                'payment_date' => $this->transactionDate,
            ]);

        // Reconcile
        $this->service->reconcileMultiple(
            [$bankLine->id],
            [$payment->id],
            $this->user
        );

        // Assert reconciliation was successful
        expect($bankLine->fresh()->is_reconciled)->toBeTrue();
        expect($payment->fresh()->status)->toBe(PaymentStatus::Reconciled);
    });

    it('can reconcile cross-currency transactions (USD statement + IQD payment)', function () {
        // Create USD bank statement line
        $bankLine = BankStatementLine::factory()
            ->for($this->usdBankStatement)
            ->create([
                'amount' => Money::of(100, 'USD'), // $100.00
                'is_reconciled' => false,
                'date' => $this->transactionDate,
            ]);

        // Create IQD payment equivalent to $100 at 1460 rate = 146,000 IQD
        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->company->currency) // IQD
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(146000, 'IQD'), // 146,000.000 IQD
                'payment_type' => PaymentType::Inbound,
                'status' => PaymentStatus::Confirmed,
                'payment_date' => $this->transactionDate,
            ]);

        // Reconcile - should convert IQD payment to USD for comparison
        $this->service->reconcileMultiple(
            [$bankLine->id],
            [$payment->id],
            $this->user
        );

        // Assert reconciliation was successful
        expect($bankLine->fresh()->is_reconciled)->toBeTrue();
        expect($payment->fresh()->status)->toBe(PaymentStatus::Reconciled);
    });

    it('throws exception when cross-currency totals do not match after conversion', function () {
        // Create USD bank statement line
        $bankLine = BankStatementLine::factory()
            ->for($this->usdBankStatement)
            ->create([
                'amount' => Money::of(100, 'USD'), // $100.00
                'is_reconciled' => false,
                'date' => $this->transactionDate,
            ]);

        // Create IQD payment that doesn't match after conversion
        // $50 equivalent at 1460 rate = 73,000 IQD (not matching $100)
        $payment = Payment::factory()
            ->for($this->company)
            ->for($this->company->currency) // IQD
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(73000, 'IQD'), // 73,000.000 IQD = $50
                'payment_type' => PaymentType::Inbound,
                'status' => PaymentStatus::Confirmed,
                'payment_date' => $this->transactionDate,
            ]);

        // Should throw exception due to mismatch
        expect(fn () => $this->service->reconcileMultiple(
            [$bankLine->id],
            [$payment->id],
            $this->user
        ))->toThrow(RuntimeException::class, 'Bank statement lines total does not match payments total');
    });

    it('handles multiple cross-currency payments correctly', function () {
        // Create USD bank statement lines totaling $200
        $bankLine1 = BankStatementLine::factory()
            ->for($this->usdBankStatement)
            ->create([
                'amount' => Money::of(100, 'USD'),
                'is_reconciled' => false,
                'date' => $this->transactionDate,
            ]);

        $bankLine2 = BankStatementLine::factory()
            ->for($this->usdBankStatement)
            ->create([
                'amount' => Money::of(100, 'USD'),
                'is_reconciled' => false,
                'date' => $this->transactionDate,
            ]);

        // Create mixed currency payments: 1 USD + 1 IQD totaling $200
        $usdPayment = Payment::factory()
            ->for($this->company)
            ->for($this->usdCurrency)
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(100, 'USD'), // $100
                'payment_type' => PaymentType::Inbound,
                'status' => PaymentStatus::Confirmed,
                'payment_date' => $this->transactionDate,
            ]);

        $iqdPayment = Payment::factory()
            ->for($this->company)
            ->for($this->company->currency) // IQD
            ->for($this->bankJournal)
            ->create([
                'amount' => Money::of(146000, 'IQD'), // 146,000 IQD = $100
                'payment_type' => PaymentType::Inbound,
                'status' => PaymentStatus::Confirmed,
                'payment_date' => $this->transactionDate,
            ]);

        // Reconcile
        $this->service->reconcileMultiple(
            [$bankLine1->id, $bankLine2->id],
            [$usdPayment->id, $iqdPayment->id],
            $this->user
        );

        // Assert all items were reconciled
        expect($bankLine1->fresh()->is_reconciled)->toBeTrue();
        expect($bankLine2->fresh()->is_reconciled)->toBeTrue();
        expect($usdPayment->fresh()->status)->toBe(PaymentStatus::Reconciled);
        expect($iqdPayment->fresh()->status)->toBe(PaymentStatus::Reconciled);
    });
});

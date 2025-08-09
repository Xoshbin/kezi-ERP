<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Currency;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Journal;
use App\Services\BankReconciliationService;
use App\Enums\Accounting\JournalType;
use Brick\Money\Money;
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
        expect($payment1->fresh()->status)->toBe('Reconciled');
        expect($payment2->fresh()->status)->toBe('Reconciled');

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

        expect(fn() => $this->service->reconcileMultiple(
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
        expect($payment->fresh()->status)->toBe('Reconciled');
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
            ->create(['status' => 'Reconciled']);

        $unreconciledPayment = Payment::factory()
            ->for($this->company)
            ->for($this->company->currency)
            ->for($this->bankJournal)
            ->create(['status' => 'confirmed']);

        $result = $this->service->getUnreconciledPayments($this->company->id);

        expect($result)->toHaveCount(1);
        expect($result->first()->id)->toBe($unreconciledPayment->id);
    });
});

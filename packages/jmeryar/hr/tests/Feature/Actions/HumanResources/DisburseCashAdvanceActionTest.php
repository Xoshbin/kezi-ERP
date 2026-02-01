<?php

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Enums\Accounting\JournalType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\HR\Actions\HumanResources\DisburseCashAdvanceAction;
use Jmeryar\HR\Enums\CashAdvanceStatus;
use Jmeryar\HR\Models\CashAdvance;
use Jmeryar\Payment\Models\Payment;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->disbursementUser = User::factory()->create();
    $this->action = app(DisburseCashAdvanceAction::class);

    // Configure the required accounts for disbursement
    $this->employeeAdvanceReceivableAccount = Account::factory()->for($this->company)->create([
        'name' => 'Employee Advance Receivable',
        'type' => 'current_assets',
    ]);

    $this->bankAccount = Account::factory()->for($this->company)->create([
        'name' => 'Cash/Bank Account',
        'type' => 'current_assets',
    ]);

    $this->cashJournal = Journal::factory()->for($this->company)->create([
        'type' => JournalType::Bank,
    ]);

    // Set company defaults
    $this->company->update([
        'default_employee_advance_receivable_account_id' => $this->employeeAdvanceReceivableAccount->id,
        'default_cash_journal_id' => $this->cashJournal->id,
    ]);
});

describe('DisburseCashAdvanceAction', function () {
    it('can disburse an approved cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Approved,
            'requested_amount' => 1000,
            'approved_amount' => 1000,
        ]);

        $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser);

        expect($cashAdvance->refresh())
            ->status->toBe(CashAdvanceStatus::Disbursed)
            ->disbursed_at->not->toBeNull()
            ->disbursed_by_user_id->toBe($this->disbursementUser->id);
    });

    it('sets the disbursed amount to approved amount', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Approved,
            'requested_amount' => 1000,
            'approved_amount' => 750,
        ]);

        $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser);

        $cashAdvance->refresh();
        expect($cashAdvance->disbursed_amount->isEqualTo(Money::of(750, $cashAdvance->currency->code)))->toBeTrue();
    });

    it('creates a journal entry for the disbursement', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Approved,
            'requested_amount' => 1000,
            'approved_amount' => 1000,
        ]);

        $initialJournalEntryCount = JournalEntry::count();

        $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser);

        $cashAdvance->refresh();
        expect(JournalEntry::count())->toBeGreaterThan($initialJournalEntryCount);
        expect($cashAdvance->disbursement_journal_entry_id)->not->toBeNull();
    });

    it('creates journal entry with correct double-entry accounting', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Approved,
            'requested_amount' => 500,
            'approved_amount' => 500,
        ]);

        $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser);

        $cashAdvance->refresh();
        $journalEntry = JournalEntry::find($cashAdvance->disbursement_journal_entry_id);

        expect($journalEntry)->not->toBeNull();
        expect($journalEntry->lines)->toHaveCount(2);

        // Dr Employee Advance Receivable
        $debitLine = $journalEntry->lines->firstWhere('account_id', $this->employeeAdvanceReceivableAccount->id);
        expect($debitLine)->not->toBeNull();
        expect($debitLine->debit->isEqualTo(Money::of(500, $cashAdvance->currency->code)))->toBeTrue();

        // Cr Bank Account
        $creditLine = $journalEntry->lines->firstWhere('account_id', $this->bankAccount->id);
        expect($creditLine)->not->toBeNull();
        expect($creditLine->credit->isEqualTo(Money::of(500, $cashAdvance->currency->code)))->toBeTrue();
    });

    it('creates a payment record for the disbursement', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Approved,
            'requested_amount' => 1000,
            'approved_amount' => 1000,
        ]);

        $initialPaymentCount = Payment::count();

        $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser);

        $cashAdvance->refresh();
        expect(Payment::count())->toBeGreaterThan($initialPaymentCount);
        expect($cashAdvance->payment_id)->not->toBeNull();
    });

    it('cannot disburse a draft cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Draft,
            'requested_amount' => 1000,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser))
            ->toThrow(\InvalidArgumentException::class, 'Only approved cash advances can be disbursed.');
    });

    it('cannot disburse a pending approval cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::PendingApproval,
            'requested_amount' => 1000,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser))
            ->toThrow(\InvalidArgumentException::class, 'Only approved cash advances can be disbursed.');
    });

    it('cannot disburse an already disbursed cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Disbursed,
            'requested_amount' => 1000,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser))
            ->toThrow(\InvalidArgumentException::class, 'Only approved cash advances can be disbursed.');
    });

    it('cannot disburse a rejected cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Rejected,
            'requested_amount' => 1000,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser))
            ->toThrow(\InvalidArgumentException::class, 'Only approved cash advances can be disbursed.');
    });

    it('cannot disburse a settled cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Settled,
            'requested_amount' => 1000,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser))
            ->toThrow(\InvalidArgumentException::class, 'Only approved cash advances can be disbursed.');
    });

    it('throws exception if employee advance receivable account not configured', function () {
        $this->company->update([
            'default_employee_advance_receivable_account_id' => null,
        ]);

        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Approved,
            'requested_amount' => 1000,
            'approved_amount' => 1000,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser))
            ->toThrow(\RuntimeException::class, 'Employee advance receivable account not configured for company.');
    });

    it('uses fallback journal when default cash journal not configured', function () {
        // Remove default cash journal but ensure a cash/bank journal exists
        $this->company->update([
            'default_cash_journal_id' => null,
        ]);

        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Approved,
            'requested_amount' => 1000,
            'approved_amount' => 1000,
        ]);

        // Should not throw - will fallback to any cash/bank journal
        $this->action->execute($cashAdvance, $this->bankAccount->id, $this->disbursementUser);

        expect($cashAdvance->refresh()->status)->toBe(CashAdvanceStatus::Disbursed);
    });
});

<?php

use Brick\Money\Money;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Tests\Traits\WithConfiguredCompany;
use Modules\Accounting\Enums\Loans\LoanType;
use Modules\Accounting\Models\LoanAgreement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Loans\ScheduleMethod;
use Modules\Accounting\Actions\Loans\AccrueLoanInterestAction;
use Modules\Accounting\Actions\Loans\ComputeLoanScheduleAction;
use Modules\Accounting\Actions\Loans\BuildLoanPaymentJournalEntryAction;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('posts repayment JE for a payable loan, interest-first', function () {
    $code = $this->company->currency->code;

    $loan = LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('10000', $code),
        'loan_type' => LoanType::Payable,
        'duration_months' => 12,
        'schedule_method' => ScheduleMethod::Annuity,
        'interest_rate' => 12.0,
        'eir_enabled' => false,
    ]);

    app(ComputeLoanScheduleAction::class)->execute($loan);

    $bank = Account::factory()->for($this->company)->create();
    $loanLiab = Account::factory()->for($this->company)->create();
    $accruedInterest = Account::factory()->for($this->company)->create();
    $interestExpense = Account::factory()->for($this->company)->create();
    $journal = Journal::factory()->for($this->company)->create();

    // Accrue month 1 interest to simulate month-end
    app(AccrueLoanInterestAction::class)->execute(
        loan: $loan,
        user: $this->user,
        journalId: $journal->id,
        interestAccountId: $interestExpense->id,
        accruedInterestAccountId: $accruedInterest->id,
        forMonthSequence: 1,
    );

    $je = app(BuildLoanPaymentJournalEntryAction::class)->execute(
        loan: $loan,
        user: $this->user,
        journalId: $journal->id,
        bankAccountId: $bank->id,
        loanAccountId: $loanLiab->id,
        accruedInterestAccountId: $accruedInterest->id,
        forMonthSequence: 1,
    );

    expect($je->is_posted)->toBeTrue();

    $entry = $loan->scheduleEntries()->where('sequence', 1)->firstOrFail();
    $int = $entry->interest_component;
    $prin = $entry->principal_component;

    $drAccrued = $je->lines()->where('account_id', $accruedInterest->id)->firstOrFail()->debit;
    $drPrincipal = $je->lines()->where('account_id', $loanLiab->id)->firstOrFail()->debit;
    $crBank = $je->lines()->where('account_id', $bank->id)->firstOrFail()->credit;

    expect($drAccrued->isEqualTo($int))->toBeTrue();
    expect($drPrincipal->isEqualTo($prin))->toBeTrue();
    expect($crBank->isEqualTo($int->plus($prin)))->toBeTrue();
});

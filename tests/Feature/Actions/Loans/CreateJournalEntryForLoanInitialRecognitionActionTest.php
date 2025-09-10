<?php

use App\Actions\Loans\CreateJournalEntryForLoanInitialRecognitionAction;
use App\Enums\Loans\LoanType;
use App\Enums\Loans\ScheduleMethod;
use App\Models\Account;
use App\Models\Journal;
use App\Models\LoanAgreement;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('creates initial recognition journal entry for a payable loan', function () {
    $code = $this->company->currency->code;

    $loan = LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('10000', $code),
        'loan_type' => LoanType::Payable,
        'duration_months' => 12,
        'schedule_method' => ScheduleMethod::Annuity,
        'interest_rate' => 12.0,
    ]);

    $bank = Account::factory()->for($this->company)->create();
    $loanLiab = Account::factory()->for($this->company)->create();
    $journal = Journal::factory()->for($this->company)->create();

    $je = app(CreateJournalEntryForLoanInitialRecognitionAction::class)
        ->execute(
            loan: $loan,
            user: $this->user,
            journalId: $journal->id,
            bankAccountId: $bank->id,
            loanAccountId: $loanLiab->id,
        );

    expect($je->is_posted)->toBeTrue();
    expect($je->lines)->toHaveCount(2);

    $debit = $je->lines()->where('account_id', $bank->id)->firstOrFail()->debit;
    $credit = $je->lines()->where('account_id', $loanLiab->id)->firstOrFail()->credit;

    expect($debit->isEqualTo(Money::of('10000', $code)))->toBeTrue();
    expect($credit->isEqualTo(Money::of('10000', $code)))->toBeTrue();
});

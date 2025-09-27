<?php

use App\Actions\Loans\AccrueLoanInterestAction;
use App\Actions\Loans\ComputeLoanScheduleAction;
use App\Enums\Loans\LoanType;
use App\Enums\Loans\ScheduleMethod;
use App\Models\Journal;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('accrues monthly interest for a payable loan', function () {
    $code = $this->company->currency->code;

    $loan = \Modules\Accounting\Models\LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('10000', $code),
        'loan_type' => LoanType::Payable,
        'duration_months' => 12,
        'schedule_method' => ScheduleMethod::Annuity,
        'interest_rate' => 12.0,
        'eir_enabled' => false,
    ]);

    app(ComputeLoanScheduleAction::class)->execute($loan);

    $interestExpense = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();
    $accruedInterest = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();
    $journal = Journal::factory()->for($this->company)->create();

    $je = app(AccrueLoanInterestAction::class)->execute(
        loan: $loan,
        user: $this->user,
        journalId: $journal->id,
        interestAccountId: $interestExpense->id,
        accruedInterestAccountId: $accruedInterest->id,
        forMonthSequence: 1,
    );

    expect($je->is_posted)->toBeTrue();

    $lineExpense = $je->lines()->where('account_id', $interestExpense->id)->firstOrFail();
    $lineAccrued = $je->lines()->where('account_id', $accruedInterest->id)->firstOrFail();

    expect(round($lineExpense->debit->getAmount()->toFloat(), 2))->toBe(100.00);
    expect(round($lineAccrued->credit->getAmount()->toFloat(), 2))->toBe(100.00);
});

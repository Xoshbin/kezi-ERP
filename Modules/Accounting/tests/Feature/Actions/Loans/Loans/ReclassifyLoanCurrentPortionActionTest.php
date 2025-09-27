<?php

use App\Actions\Loans\ComputeLoanScheduleAction;
use App\Actions\Loans\ReclassifyLoanCurrentPortionAction;
use App\Enums\Loans\LoanType;
use App\Enums\Loans\ScheduleMethod;
use App\Models\Account;
use App\Models\Journal;
use App\Models\LoanAgreement;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('moves next-12-month principal from LT to ST for payable loan', function () {
    $code = $this->company->currency->code;

    $loan = \Modules\Accounting\Models\LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('12000', $code),
        'loan_type' => LoanType::Payable,
        'duration_months' => 24,
        'schedule_method' => ScheduleMethod::StraightLinePrincipal,
        'interest_rate' => 12.0,
        'eir_enabled' => false,
        'start_date' => now()->startOfMonth()->addMonth(),
    ]);

    app(ComputeLoanScheduleAction::class)->execute($loan);

    $lt = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();
    $st = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();
    $journal = Journal::factory()->for($this->company)->create();

    $je = app(ReclassifyLoanCurrentPortionAction::class)->execute(
        loan: $loan,
        user: $this->user,
        journalId: $journal->id,
        longTermAccountId: $lt->id,
        shortTermAccountId: $st->id,
        months: 12,
        asOfDate: now()->startOfMonth()->addMonths(12),
    );

    expect($je->is_posted)->toBeTrue();

    $drST = $je->lines()->where('account_id', $st->id)->firstOrFail()->debit;
    $crLT = $je->lines()->where('account_id', $lt->id)->firstOrFail()->credit;

    // Straight-line principal: 12000 / 24 = 500 per month -> next-12-months = 6000
    expect((int) round($drST->getAmount()->toFloat()))->toBe(6000);
    expect((int) round($crLT->getAmount()->toFloat()))->toBe(6000);
});

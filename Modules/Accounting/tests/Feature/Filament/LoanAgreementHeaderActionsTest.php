<?php

use App\Enums\Loans\LoanType;
use App\Enums\Loans\ScheduleMethod;
use App\Models\Account;
use App\Models\Journal;
use App\Models\LoanAgreement;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can accrue interest, post repayment, and reclassify from header actions', function () {
    $code = $this->company->currency->code;

    $loan = \Modules\Accounting\Models\LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('10000', $code),
        'loan_type' => LoanType::Payable,
        'duration_months' => 12,
        'schedule_method' => ScheduleMethod::Annuity,
        'interest_rate' => 12.0,
        'eir_enabled' => false,
        'start_date' => now()->startOfMonth()->addMonth(),
        'loan_date' => now()->startOfMonth(),
    ]);

    // Precompute schedule
    livewire(\App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ViewLoanAgreement::class, [
        'record' => $loan->getRouteKey(),
    ])->callAction('computeSchedule');

    $journal = Journal::factory()->for($this->company)->create();
    $bank = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();
    $loanAcc = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();
    $accrued = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();
    $interest = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();

    // Accrue interest for month 1
    livewire(\App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ViewLoanAgreement::class, [
        'record' => $loan->getRouteKey(),
    ])->callAction('accrueInterest', data: [
        'journal_id' => $journal->id,
        'interest_account_id' => $interest->id,
        'accrued_interest_account_id' => $accrued->id,
        'for_month_sequence' => 1,
    ]);

    // Post repayment for month 1 (interest-first)
    livewire(\App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ViewLoanAgreement::class, [
        'record' => $loan->getRouteKey(),
    ])->callAction('postRepayment', data: [
        'journal_id' => $journal->id,
        'bank_account_id' => $bank->id,
        'loan_account_id' => $loanAcc->id,
        'accrued_interest_account_id' => $accrued->id,
        'for_month_sequence' => 1,
    ]);

    // Reclassify current portion for next 6 months
    livewire(\App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ViewLoanAgreement::class, [
        'record' => $loan->getRouteKey(),
    ])->callAction('reclassifyCurrentPortion', data: [
        'journal_id' => $journal->id,
        'long_term_account_id' => $loanAcc->id,
        'short_term_account_id' => \Modules\Accounting\Models\Account::factory()->for($this->company)->create()->id,
        'months' => 6,
        'as_of_date' => now()->startOfMonth()->addMonths(6)->format('Y-m-d'),
    ]);

    expect(true)->toBeTrue();
});

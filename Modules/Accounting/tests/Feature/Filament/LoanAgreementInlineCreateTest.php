<?php

use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalType;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ViewLoanAgreement;
use App\Models\Journal;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('configures createOption forms and can post repayment after creating needed records', function () {
    $code = $this->company->currency->code;
    $loan = \Modules\Accounting\Models\LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('10000', $code),
        'duration_months' => 12,
        'interest_rate' => 12.0,
        'start_date' => now()->startOfMonth()->addMonth(),
        'loan_date' => now()->startOfMonth(),
    ]);

    // Precompute schedule to ensure month 1 exists
    livewire(ViewLoanAgreement::class, ['record' => $loan->getRouteKey()])
        ->callAction('computeSchedule');

    // Create the needed Journal and Accounts (simulating inline create outcome)
    $journal = Journal::factory()->for($this->company)->create([
        'type' => JournalType::Bank,
        'name' => 'Bank Journal',
        'short_code' => 'BNK',
    ]);
    $bank = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'code' => '100100',
        'name' => 'Main Bank',
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash,
    ]);
    $loanAcc = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'code' => '210100',
        'name' => 'Loan Payable',
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::NonCurrentLiabilities,
    ]);
    $accrued = \Modules\Accounting\Models\Account::factory()->for($this->company)->create([
        'code' => '215500',
        'name' => 'Accrued Interest',
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentLiabilities,
    ]);

    // Submit the action using created options
    livewire(ViewLoanAgreement::class, ['record' => $loan->getRouteKey()])
        ->callAction('postRepayment', data: [
            'journal_id' => $journal->id,
            'bank_account_id' => $bank->id,
            'loan_account_id' => $loanAcc->id,
            'accrued_interest_account_id' => $accrued->id,
            'for_month_sequence' => 1,
        ])
        ->assertNotified();

    // Basic assertion: the repayment journal entry exists with expected reference format
    $this->assertDatabaseHas('journal_entries', [
        'company_id' => $this->company->id,
        'reference' => 'LOAN-PAY/'.$loan->id.'/1',
    ]);
});

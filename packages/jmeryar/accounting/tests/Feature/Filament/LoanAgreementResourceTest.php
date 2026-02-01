<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Enums\Loans\LoanStatus;
use Jmeryar\Accounting\Enums\Loans\LoanType;
use Jmeryar\Accounting\Enums\Loans\ScheduleMethod;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\CreateLoanAgreement;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ViewLoanAgreement;
use Jmeryar\Accounting\Models\LoanAgreement;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

use Filament\Facades\Filament;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ListLoanAgreements;

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
    $this->actingAs($this->user);
});

it('scopes loan agreements to the active company', function () {
    $loanInCompany = LoanAgreement::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'LOAN-IN-COMPANY',
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $loanInOtherCompany = LoanAgreement::factory()->create([
        'company_id' => $otherCompany->id,
        'name' => 'LOAN-OUT-COMPANY',
    ]);

    livewire(ListLoanAgreements::class)
        ->searchTable('LOAN')
        ->assertCanSeeTableRecords([$loanInCompany])
        ->assertCanNotSeeTableRecords([$loanInOtherCompany]);
});

it('can render the list and create pages', function () {
    $this->get(LoanAgreementResource::getUrl('index'))->assertSuccessful();
    $this->get(LoanAgreementResource::getUrl('create'))->assertSuccessful();
});

it('can create a loan agreement with Money inputs', function () {
    livewire(CreateLoanAgreement::class)
        ->fillForm([
            'partner_id' => null,
            'loan_date' => now()->format('Y-m-d'),
            'start_date' => now()->addMonth()->format('Y-m-d'),
            'duration_months' => 12,
            'currency_id' => $this->company->currency_id,
            'principal_amount' => 10000,
            'outstanding_principal' => 0,
            'loan_type' => LoanType::Payable->value,
            'status' => LoanStatus::Draft->value,
            'schedule_method' => ScheduleMethod::Annuity->value,
            'interest_rate' => 12.0,
            'eir_enabled' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseCount('loan_agreements', 1);
});

it('can run compute schedule and recalc EIR actions from view page', function () {
    $loan = LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('10000', $this->company->currency->code),
        'loan_date' => now()->startOfMonth(),
        'start_date' => now()->startOfMonth()->addMonth(),
        'duration_months' => 12,
        'schedule_method' => ScheduleMethod::Annuity,
        'interest_rate' => 12.0,
        'eir_enabled' => true,
    ]);

    $wire = livewire(ViewLoanAgreement::class, [
        'record' => $loan->getRouteKey(),
    ]);

    $wire->callAction('computeSchedule');
    $loan->refresh();
    expect($loan->scheduleEntries()->count())->toBe(12);

    $wire->callAction('recalculateEIR');
    $loan->refresh();
    expect($loan->eir_rate)->not->toBeNull();
});

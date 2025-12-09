<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Loans\LoanStatus;
use Modules\Accounting\Enums\Loans\LoanType;
use Modules\Accounting\Enums\Loans\ScheduleMethod;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\CreateLoanAgreement;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ViewLoanAgreement;
use Modules\Accounting\Models\LoanAgreement;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
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

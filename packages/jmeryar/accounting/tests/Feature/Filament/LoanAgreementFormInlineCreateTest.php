<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Enums\Loans\LoanStatus;
use Jmeryar\Accounting\Enums\Loans\LoanType;
use Jmeryar\Accounting\Enums\Loans\ScheduleMethod;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\CreateLoanAgreement;
use Tests\Support\FilamentInlineCreate as Inline;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('can fill create loan form using newly created partner and currency', function () {
    $company = $this->company;

    // Simulate inline creation via helper
    $partner = Inline::partner(['company_id' => $company->id, 'name' => 'Inline Partner Ltd']);
    $currency = Inline::currency(['code' => 'EUR']);

    livewire(CreateLoanAgreement::class)
        ->fillForm([
            'partner_id' => $partner->id,
            'loan_date' => now()->format('Y-m-d'),
            'start_date' => now()->addMonth()->format('Y-m-d'),
            'duration_months' => 12,
            'currency_id' => $currency->id,
            'principal_amount' => 5000,
            'outstanding_principal' => 0,
            'loan_type' => LoanType::Payable->value,
            'status' => LoanStatus::Draft->value,
            'schedule_method' => ScheduleMethod::Annuity->value,
            'interest_rate' => 10.0,
            'eir_enabled' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('loan_agreements', [
        'company_id' => $company->id,
        'partner_id' => $partner->id,
        'currency_id' => $currency->id,
    ]);
});

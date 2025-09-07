<?php

use App\Enums\Partners\PartnerType;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\CreateLoanAgreement;
use App\Models\Currency;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () { $this->setupWithConfiguredCompany(); $this->actingAs($this->user); });

it('can fill create loan form using newly created partner and currency', function () {
    $company = $this->company;

    // Simulate inline create outcome by creating records
    $partner = Partner::factory()->for($company)->create([
        'name' => 'Inline Partner Ltd',
        'type' => PartnerType::Vendor,
        'email' => 'ap@inline-partner.test',
        'contact_person' => 'Alice',
    ]);
    $currency = Currency::query()->firstOrCreate(
        ['code' => 'EUR'],
        ['name' => ['en' => 'Euro'], 'symbol' => '€']
    );

    livewire(CreateLoanAgreement::class)
        ->fillForm([
            'partner_id' => $partner->id,
            'loan_date' => now()->format('Y-m-d'),
            'start_date' => now()->addMonth()->format('Y-m-d'),
            'duration_months' => 12,
            'currency_id' => $currency->id,
            'principal_amount' => 5000,
            'outstanding_principal' => 0,
            'loan_type' => \App\Enums\Loans\LoanType::Payable->value,
            'status' => \App\Enums\Loans\LoanStatus::Draft->value,
            'schedule_method' => \App\Enums\Loans\ScheduleMethod::Annuity->value,
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


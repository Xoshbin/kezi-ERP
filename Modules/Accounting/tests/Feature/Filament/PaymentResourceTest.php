<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages\CreatePayment;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages\ListPayments;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Partner;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Models\Payment;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

it('can render the payment list page', function () {
    livewire(ListPayments::class)
        ->assertSuccessful();
});

it('can list payments', function () {
    $payments = Payment::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListPayments::class)
        ->assertCanSeeTableRecords($payments)
        ->assertCountTableRecords(3);
});

it('can render create payment page', function () {
    livewire(CreatePayment::class)
        ->assertSuccessful();
});

it('can create a new payment', function () {
    $journal = Journal::factory()->create(['company_id' => $this->company->id]);
    $partner = Partner::factory()->create(['company_id' => $this->company->id]);

    $newData = [
        'payment_type' => PaymentType::Inbound->value,
        'paid_to_from_partner_id' => $partner->id,
        'amount' => 500,
        'payment_date' => now()->format('Y-m-d'),
        'journal_id' => $journal->id,
        'payment_method' => PaymentMethod::Manual->value,
        'currency_id' => $this->company->currency_id,
        'company_id' => $this->company->id,
    ];

    livewire(CreatePayment::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('payments', [
        'company_id' => $this->company->id,
        'amount' => 500000,
    ]);
});

it('scopes payments to the active company', function () {
    $paymentInCompany = Payment::factory()->create([
        'company_id' => $this->company->id,
        'reference' => 'PAY-IN-COMPANY',
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $paymentInOtherCompany = Payment::factory()->create([
        'company_id' => $otherCompany->id,
        'reference' => 'PAY-OUT-COMPANY',
    ]);

    livewire(ListPayments::class)
        ->searchTable('PAY')
        ->assertCanSeeTableRecords([$paymentInCompany])
        ->assertCanNotSeeTableRecords([$paymentInOtherCompany]);
});

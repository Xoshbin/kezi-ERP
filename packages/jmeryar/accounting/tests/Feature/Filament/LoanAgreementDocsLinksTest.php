<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\CreateLoanAgreement;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\EditLoanAgreement;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ViewLoanAgreement;
use Jmeryar\Accounting\Models\LoanAgreement;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('shows docs action on loan agreements list page header', function () {
    $this->get(LoanAgreementResource::getUrl('index'))
        ->assertSee('Loan Agreements Guide');
});

it('shows docs action on create loan agreement page', function () {
    livewire(CreateLoanAgreement::class)
        ->assertActionVisible('loan-agreements_docs');
});

it('shows docs action on edit loan agreement page', function () {
    $loan = LoanAgreement::factory()->for($this->company)->create();

    livewire(EditLoanAgreement::class, ['record' => $loan->getRouteKey()])
        ->assertActionVisible('loan-agreements_docs');
});

it('shows docs action on view loan agreement page', function () {
    $loan = LoanAgreement::factory()->for($this->company)->create();

    livewire(ViewLoanAgreement::class, ['record' => $loan->getRouteKey()])
        ->assertActionVisible('loan-agreements_docs');
});

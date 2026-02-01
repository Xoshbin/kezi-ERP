<?php

namespace Kezi\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource\Pages\CreateCheque;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource\Pages\ListCheques;
use Kezi\Payment\Models\Cheque;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

it('can render the cheque list page', function () {
    livewire(ListCheques::class)
        ->assertSuccessful();
});

it('can list cheques', function () {
    $cheques = Cheque::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListCheques::class)
        ->assertCanSeeTableRecords($cheques)
        ->assertCountTableRecords(3);
});

it('can render create cheque page', function () {
    livewire(CreateCheque::class)
        ->assertSuccessful();
});

it('scopes cheques to the active company', function () {
    $chequeInCompany = Cheque::factory()->create([
        'company_id' => $this->company->id,
        'cheque_number' => 'CHQ-IN-COMPANY',
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $chequeInOtherCompany = Cheque::factory()->create([
        'company_id' => $otherCompany->id,
        'cheque_number' => 'CHQ-OUT-COMPANY',
    ]);

    livewire(ListCheques::class)
        ->searchTable('CHQ')
        ->assertCanSeeTableRecords([$chequeInCompany])
        ->assertCanNotSeeTableRecords([$chequeInOtherCompany]);
});

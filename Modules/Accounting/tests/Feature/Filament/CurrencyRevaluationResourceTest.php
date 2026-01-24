<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\CurrencyRevaluationResource\Pages\ListCurrencyRevaluations;
use Modules\Accounting\Models\CurrencyRevaluation;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

it('can render the currency revaluation list page', function () {
    livewire(ListCurrencyRevaluations::class)
        ->assertSuccessful();
});

it('can list currency revaluations', function () {
    $revaluations = CurrencyRevaluation::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListCurrencyRevaluations::class)
        ->assertCanSeeTableRecords($revaluations)
        ->assertCountTableRecords(3);
});

it('scopes currency revaluations to the active company', function () {
    $revalInCompany = CurrencyRevaluation::factory()->create([
        'company_id' => $this->company->id,
        'description' => 'REVAL-IN-COMPANY',
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $revalInOtherCompany = CurrencyRevaluation::factory()->create([
        'company_id' => $otherCompany->id,
        'description' => 'REVAL-OUT-COMPANY',
    ]);

    livewire(ListCurrencyRevaluations::class)
        ->searchTable('REVAL')
        ->assertCanSeeTableRecords([$revalInCompany])
        ->assertCanNotSeeTableRecords([$revalInOtherCompany]);
});

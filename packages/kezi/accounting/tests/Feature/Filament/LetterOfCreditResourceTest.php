<?php

namespace Kezi\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages\ListLetterOfCredits;
use Kezi\Payment\Models\LetterOfCredit;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

it('can render the letter of credit list page', function () {
    livewire(ListLetterOfCredits::class)
        ->assertSuccessful();
});

it('can list letters of credit', function () {
    $lcs = LetterOfCredit::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListLetterOfCredits::class)
        ->assertCanSeeTableRecords($lcs)
        ->assertCountTableRecords(3);
});

it('scopes letters of credit to the active company', function () {
    $lcInCompany = LetterOfCredit::factory()->create([
        'company_id' => $this->company->id,
        'lc_number' => 'LC-IN-COMPANY',
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $lcInOtherCompany = LetterOfCredit::factory()->create([
        'company_id' => $otherCompany->id,
        'lc_number' => 'LC-OUT-COMPANY',
    ]);

    livewire(ListLetterOfCredits::class)
        ->searchTable('LC')
        ->assertCanSeeTableRecords([$lcInCompany])
        ->assertCanNotSeeTableRecords([$lcInOtherCompany]);
});

<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\Pages\CreateAccountGroup;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\Pages\ListAccountGroups;
use Modules\Accounting\Models\AccountGroup;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

it('can render the account group list page', function () {
    livewire(ListAccountGroups::class)
        ->assertSuccessful();
});

it('can list account groups', function () {
    $groups = AccountGroup::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListAccountGroups::class)
        ->assertCanSeeTableRecords($groups)
        ->assertCountTableRecords(3);
});

it('can render create account group page', function () {
    livewire(CreateAccountGroup::class)
        ->assertSuccessful();
});

it('can create a new account group', function () {
    $newData = [
        'code_prefix_start' => '10',
        'code_prefix_end' => '19',
        'name' => 'Current Assets',
        'level' => 1,
        'company_id' => $this->company->id,
    ];

    livewire(CreateAccountGroup::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('account_groups', [
        'company_id' => $this->company->id,
        'code_prefix_start' => '10',
        'name' => json_encode(['en' => 'Current Assets']),
    ]);
});

it('scopes account groups to the active company', function () {
    $groupInCompany = AccountGroup::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'GROUP-IN-COMPANY',
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $groupInOtherCompany = AccountGroup::factory()->create([
        'company_id' => $otherCompany->id,
        'name' => 'GROUP-OUT-COMPANY',
    ]);

    livewire(ListAccountGroups::class)
        ->searchTable('GROUP')
        ->assertCanSeeTableRecords([$groupInCompany])
        ->assertCanNotSeeTableRecords([$groupInOtherCompany]);
});

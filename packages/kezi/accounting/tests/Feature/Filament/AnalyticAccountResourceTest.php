<?php

namespace Kezi\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages\CreateAnalyticAccount;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages\EditAnalyticAccount;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages\ListAnalyticAccounts;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\RelationManagers\AnalyticPlansRelationManager;
use Kezi\Accounting\Models\AnalyticAccount;
use Kezi\Accounting\Models\AnalyticPlan;
use Kezi\Foundation\Models\Currency;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can render the analytic account list page', function () {
    livewire(ListAnalyticAccounts::class)
        ->assertSuccessful();
});

it('can list analytic accounts', function () {
    $accounts = AnalyticAccount::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListAnalyticAccounts::class)
        ->assertCanSeeTableRecords($accounts)
        ->assertCountTableRecords(3);
});

it('can render create analytic account page', function () {
    livewire(CreateAnalyticAccount::class)
        ->assertSuccessful();
});

it('can create a new analytic account', function () {
    $currency = Currency::factory()->create();

    $newData = [
        'company_id' => $this->company->id,
        'currency_id' => $currency->id,
        'name' => 'IT Department',
        'reference' => 'DEPT-IT',
        'is_active' => true,
    ];

    livewire(CreateAnalyticAccount::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('analytic_accounts', [
        'company_id' => $this->company->id,
        'name' => 'IT Department',
        'reference' => 'DEPT-IT',
        'is_active' => true,
    ]);
});

it('can render edit analytic account page', function () {
    $account = AnalyticAccount::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditAnalyticAccount::class, [
        'record' => $account->getRouteKey(),
    ])
        ->assertSuccessful();
});

it('can update an analytic account', function () {
    $account = AnalyticAccount::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Old Name',
    ]);

    livewire(EditAnalyticAccount::class, [
        'record' => $account->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'New Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($account->refresh()->name)->toBe('New Name');
});

it('can delete an analytic account', function () {
    $account = AnalyticAccount::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditAnalyticAccount::class, [
        'record' => $account->getRouteKey(),
    ])
        ->callAction('delete')
        ->assertHasNoActionErrors();

    $this->assertDatabaseMissing('analytic_accounts', [
        'id' => $account->id,
    ]);
});

it('can attach and detach analytic plans via relation manager', function () {
    $account = AnalyticAccount::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $plan = AnalyticPlan::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(AnalyticPlansRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => EditAnalyticAccount::class,
    ])
        ->callTableAction('attach', data: [
            'recordId' => $plan->id,
            'company_id' => $this->company->id,
        ])
        ->assertHasNoTableActionErrors();

    expect($account->analyticPlans()->where('analytic_plan_id', $plan->id)->exists())->toBeTrue();

    livewire(AnalyticPlansRelationManager::class, [
        'ownerRecord' => $account,
        'pageClass' => EditAnalyticAccount::class,
    ])
        ->callTableAction('detach', $plan)
        ->assertHasNoTableActionErrors();

    expect($account->analyticPlans()->where('analytic_plan_id', $plan->id)->exists())->toBeFalse();
});

it('scopes analytic accounts to the active company', function () {
    $accountInCompany = AnalyticAccount::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $accountInOtherCompany = AnalyticAccount::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    livewire(ListAnalyticAccounts::class)
        ->assertCanSeeTableRecords([$accountInCompany])
        ->assertCanNotSeeTableRecords([$accountInOtherCompany]);
});

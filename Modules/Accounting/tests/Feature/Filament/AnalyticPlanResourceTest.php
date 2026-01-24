<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\CreateAnalyticPlan;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\EditAnalyticPlan;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\ListAnalyticPlans;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\RelationManagers\AnalyticAccountsRelationManager;
use Modules\Accounting\Models\AnalyticAccount;
use Modules\Accounting\Models\AnalyticPlan;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can render the analytic plan list page', function () {
    livewire(ListAnalyticPlans::class)
        ->assertSuccessful();
});

it('can list analytic plans', function () {
    $plans = AnalyticPlan::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListAnalyticPlans::class)
        ->assertCanSeeTableRecords($plans)
        ->assertCountTableRecords(3);
});

it('can render create analytic plan page', function () {
    livewire(CreateAnalyticPlan::class)
        ->assertSuccessful();
});

it('can create a new analytic plan', function () {
    $newData = [
        'company_id' => $this->company->id,
        'name' => 'Project Dimension',
    ];

    livewire(CreateAnalyticPlan::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('analytic_plans', [
        'company_id' => $this->company->id,
        'name' => json_encode(['en' => 'Project Dimension']),
    ]);
});

it('can render edit analytic plan page', function () {
    $plan = AnalyticPlan::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditAnalyticPlan::class, [
        'record' => $plan->getRouteKey(),
    ])
        ->assertSuccessful();
});

it('can update an analytic plan', function () {
    $plan = AnalyticPlan::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditAnalyticPlan::class, [
        'record' => $plan->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'Updated Dimension Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($plan->refresh()->getTranslation('name', 'en'))->toBe('Updated Dimension Name');
});

it('can delete an analytic plan', function () {
    $plan = AnalyticPlan::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditAnalyticPlan::class, [
        'record' => $plan->getRouteKey(),
    ])
        ->callAction('delete')
        ->assertHasNoActionErrors();

    $this->assertDatabaseMissing('analytic_plans', [
        'id' => $plan->id,
    ]);
});

it('can attach and detach analytic accounts via relation manager', function () {
    $plan = AnalyticPlan::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $account = AnalyticAccount::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(AnalyticAccountsRelationManager::class, [
        'ownerRecord' => $plan,
        'pageClass' => EditAnalyticPlan::class,
    ])
        ->callTableAction('attach', data: [
            'recordId' => $account->id,
        ])
        ->assertHasNoTableActionErrors();

    expect($plan->analyticAccounts()->where('analytic_account_id', $account->id)->exists())->toBeTrue();

    livewire(AnalyticAccountsRelationManager::class, [
        'ownerRecord' => $plan,
        'pageClass' => EditAnalyticPlan::class,
    ])
        ->callTableAction('detach', $account)
        ->assertHasNoTableActionErrors();

    expect($plan->analyticAccounts()->where('analytic_account_id', $account->id)->exists())->toBeFalse();
});

it('scopes analytic plans to the active company', function () {
    $planInCompany = AnalyticPlan::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $planInOtherCompany = AnalyticPlan::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    livewire(ListAnalyticPlans::class)
        ->assertCanSeeTableRecords([$planInCompany])
        ->assertCanNotSeeTableRecords([$planInOtherCompany]);
});

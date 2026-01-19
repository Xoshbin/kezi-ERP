<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Assets\AssetStatus;
use Modules\Accounting\Enums\Assets\DepreciationEntryStatus;
use Modules\Accounting\Enums\Assets\DepreciationMethod;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\AssetResource;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages\EditAsset;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Asset;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);

    $this->assetAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::FixedAssets,
    ]);

    $this->depreciationAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Depreciation,
    ]);

    $this->accumulatedDeprAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::FixedAssets,
    ]);
});

describe('AssetResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(AssetResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can compute depreciation board', function () {
        $this->withoutExceptionHandling();

        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'purchase_date' => now(),
            'purchase_value' => 2000,
            'useful_life_years' => 5,
            'depreciation_method' => DepreciationMethod::StraightLine,
            'status' => AssetStatus::Draft,
            'currency_id' => $this->company->currency_id,
            'asset_account_id' => $this->assetAccount->id,
            'depreciation_expense_account_id' => $this->depreciationAccount->id,
            'accumulated_depreciation_account_id' => $this->accumulatedDeprAccount->id,
        ]);

        livewire(EditAsset::class, [
            'record' => $asset->getRouteKey(),
        ])
            ->callAction('compute_depreciation_board')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('depreciation_entries', [
            'asset_id' => $asset->id,
            'status' => DepreciationEntryStatus::Draft->value,
        ]);

        // Verify that entries have been created (for a 5-year life with straight line, we expect multiple entries)
        expect($asset->depreciationEntries()->count())->toBeGreaterThan(0);
    });
});

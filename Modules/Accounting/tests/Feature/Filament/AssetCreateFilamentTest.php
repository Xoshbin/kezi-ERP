<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Assets\DepreciationMethod;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages\CreateAsset;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Asset;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    // Set up Filament tenant context
    Filament::setTenant($this->company);

    $this->assetAccount = Account::factory()->for($this->company)->create(['type' => 'fixed_assets']);
    $this->depreciationExpenseAccount = Account::factory()->for($this->company)->create(['type' => 'expense']);
    $this->accumulatedDepreciationAccount = Account::factory()->for($this->company)->create(['type' => 'non_current_assets']);
});

test('asset can be created via filament form with string date', function () {
    // This test reproduces the TypeError where purchase_date string is passed to DTO expecting Carbon
    livewire(CreateAsset::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'name' => 'Test Asset From Filament',
            'purchase_date' => '2024-01-15', // String format from DatePicker
            'purchase_value' => 10000, // $10,000 in major units
            'salvage_value' => 1000, // $1,000 in major units
            'useful_life_years' => 5,
            'depreciation_method' => DepreciationMethod::StraightLine->value,
            'asset_account_id' => $this->assetAccount->id,
            'depreciation_expense_account_id' => $this->depreciationExpenseAccount->id,
            'accumulated_depreciation_account_id' => $this->accumulatedDepreciationAccount->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify asset was created
    $this->assertDatabaseHas('assets', [
        'name' => 'Test Asset From Filament',
        'company_id' => $this->company->id,
    ]);

    $asset = Asset::where('name', 'Test Asset From Filament')->first();
    expect($asset)->not->toBeNull();
    expect($asset->purchase_date->format('Y-m-d'))->toBe('2024-01-15');
});

<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages\CreateAsset;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Assets\Pages\EditAsset;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Asset;
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

test('asset can be updated via filament form with string date', function () {
    // Create an asset first using the factory
    $currencyCode = $this->company->currency->code;
    $asset = Asset::factory()->for($this->company)->create([
        'name' => 'Original Asset Name',
        'purchase_date' => now()->subYear(),
        'purchase_value' => \Brick\Money\Money::of(10000, $currencyCode),
        'salvage_value' => \Brick\Money\Money::of(1000, $currencyCode),
        'useful_life_years' => 5,
        'depreciation_method' => DepreciationMethod::StraightLine,
        'asset_account_id' => $this->assetAccount->id,
        'depreciation_expense_account_id' => $this->depreciationExpenseAccount->id,
        'accumulated_depreciation_account_id' => $this->accumulatedDepreciationAccount->id,
    ]);

    // Test editing the asset via Filament form
    livewire(EditAsset::class, ['record' => $asset->id])
        ->fillForm([
            'company_id' => $this->company->id, // Form includes company_id but DTO doesn't accept it
            'name' => 'Updated Asset Name',
            'purchase_date' => '2024-06-20', // String format from DatePicker
            'purchase_value' => 15000, // Updated value in major units
            'salvage_value' => 2000, // Updated salvage value
            'useful_life_years' => 7,
            'depreciation_method' => DepreciationMethod::StraightLine->value,
            'asset_account_id' => $this->assetAccount->id,
            'depreciation_expense_account_id' => $this->depreciationExpenseAccount->id,
            'accumulated_depreciation_account_id' => $this->accumulatedDepreciationAccount->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Verify asset was updated
    $asset->refresh();
    expect($asset->name)->toBe('Updated Asset Name');
    expect($asset->purchase_date->format('Y-m-d'))->toBe('2024-06-20');
    expect($asset->useful_life_years)->toBe(7);
});

test('asset can be created with prorata temporis enabled', function () {
    livewire(CreateAsset::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'name' => 'Prorata Asset',
            'purchase_date' => '2024-01-15',
            'purchase_value' => 12000,
            'salvage_value' => 0,
            'useful_life_years' => 3,
            'depreciation_method' => DepreciationMethod::StraightLine->value,
            'asset_account_id' => $this->assetAccount->id,
            'depreciation_expense_account_id' => $this->depreciationExpenseAccount->id,
            'accumulated_depreciation_account_id' => $this->accumulatedDepreciationAccount->id,
            'currency_id' => $this->company->currency_id,
            'prorata_temporis' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('assets', [
        'name' => 'Prorata Asset',
        'prorata_temporis' => true,
    ]);
});

test('asset with declining balance method requires declining factor', function () {
    livewire(CreateAsset::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'name' => 'Declining Asset',
            'purchase_date' => '2024-01-01',
            'purchase_value' => 10000,
            'salvage_value' => 0,
            'useful_life_years' => 5,
            'depreciation_method' => DepreciationMethod::Declining->value,
            'declining_factor' => null,
            'asset_account_id' => $this->assetAccount->id,
            'depreciation_expense_account_id' => $this->depreciationExpenseAccount->id,
            'accumulated_depreciation_account_id' => $this->accumulatedDepreciationAccount->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->call('create')
        ->assertHasErrors(['data.declining_factor' => 'required']);
});

test('asset can be created with declining balance and factor', function () {
    livewire(CreateAsset::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'name' => 'Declining Asset Fix',
            'purchase_date' => '2024-01-01',
            'purchase_value' => 10000,
            'salvage_value' => 0,
            'useful_life_years' => 5,
            'depreciation_method' => DepreciationMethod::Declining->value,
            'declining_factor' => 2.5,
            'asset_account_id' => $this->assetAccount->id,
            'depreciation_expense_account_id' => $this->depreciationExpenseAccount->id,
            'accumulated_depreciation_account_id' => $this->accumulatedDepreciationAccount->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('assets', [
        'name' => 'Declining Asset Fix',
        'depreciation_method' => DepreciationMethod::Declining->value,
        'declining_factor' => 2.5,
    ]);
});

test('asset can be created with sum of digits method', function () {
    livewire(CreateAsset::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'name' => 'SYD Asset',
            'purchase_date' => '2024-01-01',
            'purchase_value' => 12000,
            'salvage_value' => 0,
            'useful_life_years' => 3,
            'depreciation_method' => DepreciationMethod::SumOfDigits->value,
            'asset_account_id' => $this->assetAccount->id,
            'depreciation_expense_account_id' => $this->depreciationExpenseAccount->id,
            'accumulated_depreciation_account_id' => $this->accumulatedDepreciationAccount->id,
            'currency_id' => $this->company->currency_id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('assets', [
        'name' => 'SYD Asset',
        'depreciation_method' => DepreciationMethod::SumOfDigits->value,
    ]);
});

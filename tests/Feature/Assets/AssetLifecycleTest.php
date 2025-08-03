<?php

use App\Models\User;
use Brick\Money\Money;
use App\Models\Account;
use App\Models\Company;
use App\Enums\Assets\AssetStatus;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithConfiguredCompany;
use App\Enums\Assets\DepreciationMethod;
use App\Actions\Assets\CreateAssetAction;
use App\Enums\Assets\DepreciationEntryStatus;
use App\Exceptions\UpdateNotAllowedException;
use App\Exceptions\DeletionNotAllowedException;
use App\DataTransferObjects\Assets\CreateAssetDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {

    $this->assetAccount = Account::factory()->for($this->company)->create(['type' => 'Fixed Asset']);
    $this->depreciationExpenseAccount = Account::factory()->for($this->company)->create(['type' => 'Expense']);
    $this->accumulatedDepreciationAccount = Account::factory()->for($this->company)->create(['type' => 'Accumulated Depreciation']);
});

test('asset can be created manually and confirmed', function () {
    // Arrange
    $currencyCode = $this->company->currency->code;
    $assetDTO = new CreateAssetDTO(
        company_id: $this->company->id,
        name: 'Test Asset',
        purchase_date: now(),
        purchase_value: 100000,
        salvage_value: 10000,
        useful_life_years: 5,
        depreciation_method: \App\Enums\Assets\DepreciationMethod::StraightLine,
        asset_account_id: $this->assetAccount->id,
        depreciation_expense_account_id: $this->depreciationExpenseAccount->id,
        accumulated_depreciation_account_id: $this->accumulatedDepreciationAccount->id,
        currency_id: $this->company->currency_id
    );

    // Act
    $asset = (new CreateAssetAction())->execute($assetDTO);

    // Assert
    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'name' => 'Test Asset',
        'status' => AssetStatus::Draft->value,
    ]);

    // Confirm the asset
    $asset->status = AssetStatus::Confirmed;
    $asset->save();

    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'status' => AssetStatus::Confirmed->value,
    ]);
});


test('confirming asset generates initial journal entry', function () {
    // Arrange
    $currencyCode = $this->company->currency->code;
    $assetDTO = new CreateAssetDTO(
        company_id: $this->company->id,
        name: 'Test Asset',
        purchase_date: now(),
        purchase_value: 100000,
        salvage_value: 10000,
        useful_life_years: 5,
        depreciation_method: \App\Enums\Assets\DepreciationMethod::StraightLine,
        asset_account_id: $this->assetAccount->id,
        depreciation_expense_account_id: $this->depreciationExpenseAccount->id,
        accumulated_depreciation_account_id: $this->accumulatedDepreciationAccount->id,
        currency_id: $this->company->currency_id
    );
    $asset = (new CreateAssetAction())->execute($assetDTO);

    // Act
    $asset->status = AssetStatus::Confirmed;
    $asset->save();

    // Assert
    $this->assertDatabaseHas('journal_entries', [
        'source_type' => 'asset',
        'source_id' => $asset->id,
        'is_posted' => true,
    ]);

    $journalEntry = $asset->journalEntries()->first();
    $this->assertModelExists($journalEntry);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->assetAccount->id,
        'debit' => 100000,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_payable_account_id,
        'credit' => 100000,
    ]);
});


test('depreciation calculation generates correct draft entries', function () {
    // Arrange
    $assetDTO = new CreateAssetDTO(
        company_id: $this->company->id,
        name: 'Test Asset',
        purchase_date: now(),
        purchase_value: 120000,
        salvage_value: 0,
        useful_life_years: 10,
        depreciation_method: \App\Enums\Assets\DepreciationMethod::StraightLine,
        asset_account_id: $this->assetAccount->id,
        depreciation_expense_account_id: $this->depreciationExpenseAccount->id,
        accumulated_depreciation_account_id: $this->accumulatedDepreciationAccount->id,
        currency_id: $this->company->currency_id
    );
    $asset = (new CreateAssetAction())->execute($assetDTO);
    $asset->status = AssetStatus::Confirmed;
    $asset->save();

    // Act
    (new \App\Actions\Assets\ComputeDepreciationScheduleAction())->execute($asset->fresh());

    // Assert
    $this->assertDatabaseCount('depreciation_entries', 120); // 10 years * 12 months
    $this->assertDatabaseHas('depreciation_entries', [
        'asset_id' => $asset->id,
        'status' => \App\Enums\Assets\DepreciationEntryStatus::Draft->value,
        'amount' => 1000, // 1200 / 120 = 10 per month
    ]);
});


test('automated depreciation job posts correct journal entries periodically', function () {
    // Arrange
    $assetDTO = new CreateAssetDTO(
        company_id: $this->company->id,
        name: 'Test Asset',
        purchase_date: now()->subMonth(),
        purchase_value: 120000,
        salvage_value: 0,
        useful_life_years: 10,
        depreciation_method: \App\Enums\Assets\DepreciationMethod::StraightLine,
        asset_account_id: $this->assetAccount->id,
        depreciation_expense_account_id: $this->depreciationExpenseAccount->id,
        accumulated_depreciation_account_id: $this->accumulatedDepreciationAccount->id,
        currency_id: $this->company->currency_id
    );
    $asset = (new CreateAssetAction())->execute($assetDTO);
    $asset->status = AssetStatus::Confirmed;
    $asset->save();
    (new \App\Actions\Assets\ComputeDepreciationScheduleAction())->execute($asset->fresh());

    // Act
    $this->artisan('app:process-depreciations');

    // Assert
    $this->assertDatabaseHas('depreciation_entries', [
        'asset_id' => $asset->id,
        'status' => \App\Enums\Assets\DepreciationEntryStatus::Posted->value,
    ]);

    $depreciationEntry = $asset->depreciationEntries()->where('status', \App\Enums\Assets\DepreciationEntryStatus::Posted)->first();
    $this->assertNotNull($depreciationEntry->journal_entry_id);

    $journalEntry = $depreciationEntry->journalEntry;
    $this->assertModelExists($journalEntry);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->depreciationExpenseAccount->id,
        'debit' => 1000,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->accumulatedDepreciationAccount->id,
        'credit' => 1000,
    ]);
});


test('posted depreciation entries are immutable and hashed', function () {
    // Arrange
    $assetDTO = new CreateAssetDTO(
        company_id: $this->company->id,
        name: 'Test Asset',
        purchase_date: now()->subMonth(),
        purchase_value: 120000,
        salvage_value: 0,
        useful_life_years: 10,
        depreciation_method: \App\Enums\Assets\DepreciationMethod::StraightLine,
        asset_account_id: $this->assetAccount->id,
        depreciation_expense_account_id: $this->depreciationExpenseAccount->id,
        accumulated_depreciation_account_id: $this->accumulatedDepreciationAccount->id,
        currency_id: $this->company->currency_id
    );
    $asset = (new CreateAssetAction())->execute($assetDTO);
    $asset->status = AssetStatus::Confirmed;
    $asset->save();
    (new \App\Actions\Assets\ComputeDepreciationScheduleAction())->execute($asset->fresh());
    $this->artisan('app:process-depreciations');

    $depreciationEntry = $asset->depreciationEntries()->where('status', \App\Enums\Assets\DepreciationEntryStatus::Posted)->first();
    $journalEntry = $depreciationEntry->journalEntry;

    // Assert immutability
    $this->expectException(\App\Exceptions\UpdateNotAllowedException::class);
    $depreciationEntry->update(['amount' => 5000]);

    $this->expectException(\App\Exceptions\DeletionNotAllowedException::class);
    $depreciationEntry->delete();

    // Assert hashing
    $this->assertNotNull($journalEntry->hash);
    $this->assertNotNull($journalEntry->previous_hash);
});


test('asset modification recomputes future depreciation schedule', function () {
    // Arrange
    $assetDTO = new CreateAssetDTO(
        company_id: $this->company->id,
        name: 'Test Asset',
        purchase_date: now()->subYears(2),
        purchase_value: 120000,
        salvage_value: 0,
        useful_life_years: 10,
        depreciation_method: \App\Enums\Assets\DepreciationMethod::StraightLine,
        asset_account_id: $this->assetAccount->id,
        depreciation_expense_account_id: $this->depreciationExpenseAccount->id,
        accumulated_depreciation_account_id: $this->accumulatedDepreciationAccount->id,
        currency_id: $this->company->currency_id
    );
    $asset = (new CreateAssetAction())->execute($assetDTO);
    $asset->status = AssetStatus::Confirmed;
    $asset->save();
    (new \App\Actions\Assets\ComputeDepreciationScheduleAction())->execute($asset->fresh());

    // Post one year of depreciation
    for ($i = 0; $i < 12; $i++) {
        $this->artisan('app:process-depreciations');
    }

    // Act
    $updateAssetDTO = new \App\DataTransferObjects\Assets\UpdateAssetDTO(
        name: 'Test Asset Updated',
        purchase_date: $asset->purchase_date,
        purchase_value: 240000,
        salvage_value: 0,
        useful_life_years: 10,
        depreciation_method: \App\Enums\Assets\DepreciationMethod::StraightLine,
        asset_account_id: $this->assetAccount->id,
        depreciation_expense_account_id: $this->depreciationExpenseAccount->id,
        accumulated_depreciation_account_id: $this->accumulatedDepreciationAccount->id,
        currency_id: $this->company->currency_id
    );
    (new \App\Actions\Assets\UpdateAssetAction())->execute($asset, $updateAssetDTO);

    // Assert
    $this->assertDatabaseCount('depreciation_entries', 120);
    $this->assertDatabaseHas('depreciation_entries', [
        'asset_id' => $asset->id,
        'status' => \App\Enums\Assets\DepreciationEntryStatus::Posted->value,
        'amount' => 1000,
    ]);
    $this->assertDatabaseHas('depreciation_entries', [
        'asset_id' => $asset->id,
        'status' => \App\Enums\Assets\DepreciationEntryStatus::Draft->value,
        'amount' => 2000,
    ]);
});


test('asset disposal correctly generates final journal entries', function () {
    // Arrange
    $assetDTO = new CreateAssetDTO(
        company_id: $this->company->id,
        name: 'Test Asset',
        purchase_date: now()->subYears(5),
        purchase_value: 120000,
        salvage_value: 0,
        useful_life_years: 10,
        depreciation_method: \App\Enums\Assets\DepreciationMethod::StraightLine,
        asset_account_id: $this->assetAccount->id,
        depreciation_expense_account_id: $this->depreciationExpenseAccount->id,
        accumulated_depreciation_account_id: $this->accumulatedDepreciationAccount->id,
        currency_id: $this->company->currency_id
    );
    $asset = (new CreateAssetAction())->execute($assetDTO);
    $asset->status = AssetStatus::Confirmed;
    $asset->save();
    (new \App\Actions\Assets\ComputeDepreciationScheduleAction())->execute($asset->fresh());

    // Post 5 years of depreciation
    for ($i = 0; $i < 60; $i++) {
        $this->artisan('app:process-depreciations');
    }

    // Act
    $disposeAssetDTO = new \App\DataTransferObjects\Assets\DisposeAssetDTO(
        disposal_date: now(),
        disposal_value: 70000,
        gain_loss_account_id: $this->company->default_gain_on_sale_account_id,
    );
    (new \App\Actions\Assets\DisposeAssetAction())->execute($asset->fresh(), $disposeAssetDTO);

    // Assert
    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'status' => AssetStatus::Sold->value,
    ]);

    $this->assertDatabaseHas('journal_entries', [
        'source_type' => 'asset_disposal',
        'source_id' => $asset->id,
        'is_posted' => true,
    ]);

    $journalEntry = $asset->journalEntries()->where('source_type', 'asset_disposal')->first();
    $this->assertModelExists($journalEntry);

    // 60000 (accumulated depreciation) + 70000 (cash) = 130000
    // 120000 (asset value) + 10000 (gain on sale) = 130000
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->accumulatedDepreciationAccount->id,
        'debit' => 60000,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_bank_account_id,
        'debit' => 70000,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->assetAccount->id,
        'credit' => 120000,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_gain_on_sale_account_id,
        'credit' => 10000,
    ]);
});

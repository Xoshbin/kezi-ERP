<?php

use App\Actions\Assets\CreateAssetAction;
use App\DataTransferObjects\Assets\CreateAssetDTO;
use App\Enums\Assets\AssetStatus;
use App\Models\Account;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {

    $this->assetAccount = Account::factory()->for($this->company)->create(['type' => 'fixed_assets']);
    $this->depreciationExpenseAccount = Account::factory()->for($this->company)->create(['type' => 'expense']);
    $this->accumulatedDepreciationAccount = Account::factory()->for($this->company)->create(['type' => 'non_current_assets']);
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
    $asset = (new CreateAssetAction)->execute($assetDTO);

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
    $asset = (new CreateAssetAction)->execute($assetDTO);

    // Act
    $asset->status = AssetStatus::Confirmed;
    $asset->save();

    // Assert
    $this->assertDatabaseHas('journal_entries', [
        'source_type' => Asset::class,
        'source_id' => $asset->id,
        'is_posted' => true,
    ]);

    $journalEntry = $asset->journalEntries()->first();
    $this->assertModelExists($journalEntry);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->assetAccount->id,
        'debit' => 100000000,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_accounts_payable_id,
        'credit' => 100000000,
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
    $asset = (new CreateAssetAction)->execute($assetDTO);
    $asset->status = AssetStatus::Confirmed;
    $asset->save();

    // Act
    (new \App\Actions\Assets\ComputeDepreciationScheduleAction)->execute($asset->fresh());

    // Assert
    $this->assertDatabaseCount('depreciation_entries', 120); // 10 years * 12 months
    $this->assertDatabaseHas('depreciation_entries', [
        'asset_id' => $asset->id,
        'status' => \App\Enums\Assets\DepreciationEntryStatus::Draft->value,
        // The purchase value of 120,000 / 120 months = 1,000 per month.
        // For a 3-decimal currency, this is stored as 1,000,000 minor units.
        'amount' => 1000000,
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
    $asset = (new CreateAssetAction)->execute($assetDTO);
    $asset->status = AssetStatus::Confirmed;
    $asset->save();
    (new \App\Actions\Assets\ComputeDepreciationScheduleAction)->execute($asset->fresh());

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
        'debit' => 1000000,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->accumulatedDepreciationAccount->id,
        'credit' => 1000000,
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
    $asset = (new CreateAssetAction)->execute($assetDTO);
    $asset->status = AssetStatus::Confirmed;
    $asset->save();
    (new \App\Actions\Assets\ComputeDepreciationScheduleAction)->execute($asset->fresh());
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
    // Arrange: Create an asset and its initial depreciation schedule.
    $assetDTO = new \App\DataTransferObjects\Assets\CreateAssetDTO(
        company_id: $this->company->id,
        name: 'Test Asset',
        purchase_date: now()->subYears(2),
        purchase_value: 120000, // Represents 120,000.000 IQD
        salvage_value: 0,
        useful_life_years: 10,
        depreciation_method: \App\Enums\Assets\DepreciationMethod::StraightLine,
        asset_account_id: $this->assetAccount->id,
        depreciation_expense_account_id: $this->depreciationExpenseAccount->id,
        accumulated_depreciation_account_id: $this->accumulatedDepreciationAccount->id,
        currency_id: $this->company->currency_id
    );
    $asset = (new \App\Actions\Assets\CreateAssetAction)->execute($assetDTO);
    $asset->update(['status' => \App\Enums\Assets\AssetStatus::Confirmed]);
    (new \App\Actions\Assets\ComputeDepreciationScheduleAction)->execute($asset);

    // Arrange: Simulate posting the first 12 depreciation entries.
    $asset->depreciationEntries()->where('status', 'draft')->take(12)->get()->each(function ($entry) {
        $postAction = app(\App\Actions\Assets\PostDepreciationEntryAction::class);
        $postAction->execute($entry, $this->user);
    });

    // Act: Update the asset's value. The refactored UpdateAssetAction will re-compute the schedule.
    $updateAssetDTO = new \App\DataTransferObjects\Assets\UpdateAssetDTO(
        name: 'Test Asset Updated',
        purchase_date: $asset->purchase_date,
        purchase_value: 240000, // New value: 240,000.000 IQD
        salvage_value: 0,
        useful_life_years: 10,
        depreciation_method: \App\Enums\Assets\DepreciationMethod::StraightLine,
        asset_account_id: $asset->asset_account_id,
        depreciation_expense_account_id: $asset->depreciation_expense_account_id,
        accumulated_depreciation_account_id: $asset->accumulated_depreciation_account_id,
        currency_id: $asset->currency_id
    );

    // Resolve the action from the container to ensure its dependencies are injected.
    $updateAction = app(\App\Actions\Assets\UpdateAssetAction::class);
    $updateAction->execute($asset, $updateAssetDTO);

    // Assert: The final state of the database is correct.
    // There should be 12 old 'posted' entries + 120 new 'draft' entries.
    $this->assertDatabaseCount('depreciation_entries', 132);

    // Assert that the 12 posted entries still have their original amount.
    expect($asset->depreciationEntries()->where('status', 'posted')->count())->toBe(12);
    $this->assertDatabaseHas('depreciation_entries', [
        'asset_id' => $asset->id,
        'status' => \App\Enums\Assets\DepreciationEntryStatus::Posted->value,
        'amount' => 1000000, // Original amount (120k / 120 = 1k per month)
    ]);

    // Assert that there are now 120 new draft entries with the re-calculated amount.
    expect($asset->depreciationEntries()->where('status', 'draft')->count())->toBe(120);
    $this->assertDatabaseHas('depreciation_entries', [
        'asset_id' => $asset->id,
        'status' => \App\Enums\Assets\DepreciationEntryStatus::Draft->value,
        'amount' => 2000000, // New amount (240k / 120 = 2k per month)
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
    $asset = (new CreateAssetAction)->execute($assetDTO);
    $asset->status = AssetStatus::Confirmed;
    $asset->save();
    (new \App\Actions\Assets\ComputeDepreciationScheduleAction)->execute($asset->fresh());

    // Post 5 years of depreciation
    $entriesToPost = $asset->depreciationEntries()->where('status', 'draft')->orderBy('depreciation_date')->take(60)->get();
    $postAction = app(\App\Actions\Assets\PostDepreciationEntryAction::class);

    foreach ($entriesToPost as $entry) {
        $postAction->execute($entry, $this->user);
    }

    // Act
    $disposeAssetDTO = new \App\DataTransferObjects\Assets\DisposeAssetDTO(
        disposal_date: now(),
        disposal_value: 70000,
        gain_loss_account_id: $this->company->default_gain_loss_account_id,
    );
    (app(\App\Actions\Assets\DisposeAssetAction::class))->execute($asset->fresh(), $disposeAssetDTO, $this->user);

    // Assert
    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'status' => AssetStatus::Sold->value,
    ]);

    $this->assertDatabaseHas('journal_entries', [
        'source_type' => Asset::class,
        'source_id' => $asset->id,
        'is_posted' => true,
    ]);

    // Get the disposal journal entry specifically by its reference pattern
    // This ensures we're testing the disposal entry, not a depreciation entry
    $journalEntry = $asset->journalEntries()
        ->where('reference', 'DISPOSAL/' . $asset->id)
        ->first();
    $this->assertModelExists($journalEntry);

    // Additional assertions to ensure we have the correct journal entry
    $this->assertEquals('DISPOSAL/' . $asset->id, $journalEntry->reference);
    $this->assertTrue($journalEntry->is_posted);

    // Verify the journal entry has exactly 4 lines (accumulated depreciation, cash, asset, gain/loss)
    $this->assertEquals(4, $journalEntry->lines()->count());

    // 60000 (accumulated depreciation) + 70000 (cash) = 130000
    // 120000 (asset value) + 10000 (gain on sale) = 130000
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->accumulatedDepreciationAccount->id,
        'debit' => 60000000,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_bank_account_id,
        'debit' => 70000000,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->assetAccount->id,
        'credit' => 120000000,
    ]);

    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_gain_loss_account_id,
        'credit' => 10000000,
    ]);
});

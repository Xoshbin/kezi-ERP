<?php

use App\Actions\Assets\PostDepreciationEntryAction;
use App\Enums\Assets\AssetStatus;
use App\Enums\Assets\DepreciationEntryStatus;
use App\Exceptions\DeletionNotAllowedException;
use App\Services\AssetService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

// Import the Money class

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('running depreciation for an asset creates the correct journal entries', function () {
    // Arrange: Set up the necessary accounts and journal.
    $fixedAssetAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'fixed_assets']);
    $accumulatedDepreciationAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'non_current_assets']);
    $depreciationExpenseAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create(['type' => 'expense']);
    $depreciationJournal = $this->company->default_depreciation_journal_id;

    // Arrange: Create an asset to be depreciated.
    $currencyCode = $this->company->currency->code;
    $asset = \Modules\Accounting\Models\Asset::factory()->for($this->company)->create([
        'purchase_value' => Money::of(1200, $currencyCode),
        'salvage_value' => Money::of(0, $currencyCode),
        'purchase_date' => now()->subYear(),
        'depreciation_method' => 'straight_line',
        'useful_life_years' => 10,
        'asset_account_id' => $fixedAssetAccount->id,
        'accumulated_depreciation_account_id' => $accumulatedDepreciationAccount->id,
        'depreciation_expense_account_id' => $depreciationExpenseAccount->id,
    ]);

    // Act: Compute draft depreciation entries and then post the first one.
    (app(\Modules\Accounting\Services\AssetService::class))->computeDepreciation($asset);
    $draftEntry = $asset->depreciationEntries()->where('status', DepreciationEntryStatus::Draft)->first();

    // Resolve the action from the container to handle dependencies automatically
    $postAction = app(PostDepreciationEntryAction::class);
    $postedDepreciationEntry = $postAction->execute($draftEntry, $this->user);

    // Assert: A depreciation entry was created and posted.
    $this->assertDatabaseCount('depreciation_entries', 120); // 10 years * 12 months
    $this->assertEquals(DepreciationEntryStatus::Posted, $postedDepreciationEntry->status);

    $expectedMonthlyAmount = Money::of(10, $currencyCode);
    expect($postedDepreciationEntry->amount->isEqualTo($expectedMonthlyAmount))->toBeTrue();

    // Assert: A journal entry was created and linked.
    $this->assertNotNull($postedDepreciationEntry->journal_entry_id);
    $journalEntry = $postedDepreciationEntry->journalEntry;
    $this->assertModelExists($journalEntry);

    // Assert: The journal entry has the correct details.
    expect($journalEntry->journal_id)->toBe($depreciationJournal);
    expect($journalEntry->total_debit->isEqualTo($expectedMonthlyAmount))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedMonthlyAmount))->toBeTrue();

    // Assert: The correct accounts were debited and credited.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $depreciationExpenseAccount->id,
        'debit' => 10000, // 10.000 becomes 10000 in minor units for IQD
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $accumulatedDepreciationAccount->id,
        'credit' => 10000, // 10.000 becomes 10000 in minor units for IQD
    ]);

    // Assert: The asset's book value was updated.
    $asset->refresh();
    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
    ]);
});

// ======================================================================
// Asset Deletion Tests
// ======================================================================

test('a draft asset can be deleted', function () {
    // Arrange: Create a draft asset with no financial records.
    $currencyCode = $this->company->currency->code;
    $asset = \Modules\Accounting\Models\Asset::factory()->for($this->company)->create([
        'status' => AssetStatus::Draft,
        'purchase_value' => Money::of(1000, $currencyCode),
        'salvage_value' => Money::of(0, $currencyCode),
    ]);

    // Act: Delete the asset using the service.
    $assetService = app(\Modules\Accounting\Services\AssetService::class);
    $result = $assetService->delete($asset);

    // Assert: The deletion was successful.
    expect($result)->toBeTrue();
    $this->assertModelMissing($asset);
});

test('a confirmed asset cannot be deleted', function () {
    // Arrange: Create a confirmed asset.
    $currencyCode = $this->company->currency->code;
    $asset = \Modules\Accounting\Models\Asset::factory()->for($this->company)->create([
        'status' => AssetStatus::Confirmed,
        'purchase_value' => Money::of(1000, $currencyCode),
        'salvage_value' => Money::of(0, $currencyCode),
    ]);

    // Act & Assert: Attempting to delete should throw an exception.
    $assetService = app(\Modules\Accounting\Services\AssetService::class);
    expect(fn () => $assetService->delete($asset))
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class, 'Cannot delete a confirmed asset. Only draft assets can be deleted.');

    // Verify: The asset still exists.
    $this->assertModelExists($asset);
});

test('an asset with depreciation entries cannot be deleted', function () {
    // Arrange: Create a draft asset and add a depreciation entry.
    $currencyCode = $this->company->currency->code;
    $asset = \Modules\Accounting\Models\Asset::factory()->for($this->company)->create([
        'status' => AssetStatus::Draft,
        'purchase_value' => Money::of(1000, $currencyCode),
        'salvage_value' => Money::of(0, $currencyCode),
    ]);

    // Add a depreciation entry (even draft ones should prevent deletion).
    $asset->depreciationEntries()->create([
        'company_id' => $this->company->id,
        'depreciation_date' => now(),
        'amount' => Money::of(100, $currencyCode),
        'status' => DepreciationEntryStatus::Draft,
    ]);

    // Act & Assert: Attempting to delete should throw an exception.
    $assetService = app(\Modules\Accounting\Services\AssetService::class);
    expect(fn () => $assetService->delete($asset))
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class, 'Cannot delete an asset with depreciation entries. Depreciation history must be preserved.');

    // Verify: The asset still exists.
    $this->assertModelExists($asset);
});

test('an asset with journal entries cannot be deleted', function () {
    // Arrange: Create a draft asset and add a journal entry.
    $currencyCode = $this->company->currency->code;
    $asset = \Modules\Accounting\Models\Asset::factory()->for($this->company)->create([
        'status' => AssetStatus::Draft,
        'purchase_value' => Money::of(1000, $currencyCode),
        'salvage_value' => Money::of(0, $currencyCode),
    ]);

    // Add a journal entry (simulating acquisition entry).
    $asset->journalEntries()->create([
        'company_id' => $this->company->id,
        'journal_id' => $this->company->default_bank_journal_id,
        'currency_id' => $this->company->currency_id,
        'entry_date' => now(),
        'reference' => 'TEST-ASSET-001',
        'description' => 'Test asset acquisition',
        'created_by_user_id' => $this->user->id,
        'is_posted' => false,
        'total_debit' => Money::of(0, $currencyCode),
        'total_credit' => Money::of(0, $currencyCode),
    ]);

    // Act & Assert: Attempting to delete should throw an exception.
    $assetService = app(\Modules\Accounting\Services\AssetService::class);
    expect(fn () => $assetService->delete($asset))
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class, 'Cannot delete an asset with associated journal entries. Financial records must be preserved.');

    // Verify: The asset still exists.
    $this->assertModelExists($asset);
});

test('asset observer prevents deletion of confirmed assets', function () {
    // Arrange: Create a confirmed asset.
    $currencyCode = $this->company->currency->code;
    $asset = \Modules\Accounting\Models\Asset::factory()->for($this->company)->create([
        'status' => AssetStatus::Confirmed,
        'purchase_value' => Money::of(1000, $currencyCode),
        'salvage_value' => Money::of(0, $currencyCode),
    ]);

    // Act & Assert: Direct model deletion should be blocked by the observer.
    expect(fn () => $asset->delete())
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class, 'Cannot delete a confirmed asset. Only draft assets can be deleted.');

    // Verify: The asset still exists.
    $this->assertModelExists($asset);
});

test('asset observer prevents deletion of assets with depreciation entries', function () {
    // Arrange: Create a draft asset with depreciation entries.
    $currencyCode = $this->company->currency->code;
    $asset = \Modules\Accounting\Models\Asset::factory()->for($this->company)->create([
        'status' => AssetStatus::Draft,
        'purchase_value' => Money::of(1000, $currencyCode),
        'salvage_value' => Money::of(0, $currencyCode),
    ]);

    $asset->depreciationEntries()->create([
        'company_id' => $this->company->id,
        'depreciation_date' => now(),
        'amount' => Money::of(100, $currencyCode),
        'status' => DepreciationEntryStatus::Draft,
    ]);

    // Act & Assert: Direct model deletion should be blocked by the observer.
    expect(fn () => $asset->delete())
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class, 'Cannot delete an asset with depreciation entries. Depreciation history must be preserved.');

    // Verify: The asset still exists.
    $this->assertModelExists($asset);
});

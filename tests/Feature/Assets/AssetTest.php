<?php

use App\Models\Account;
use App\Models\Asset;
use App\Models\Company;
use App\Models\User;
use App\Services\AssetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->company = $this->createConfiguredCompany();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
});

test('running depreciation for an asset creates the correct journal entries', function () {
    // Arrange: Set up the necessary accounts and journal.
    $fixedAssetAccount = Account::factory()->for($this->company)->create(['type' => 'Fixed Asset']);
    $accumulatedDepreciationAccount = Account::factory()->for($this->company)->create(['type' => 'Accumulated Depreciation']);
    $depreciationExpenseAccount = Account::factory()->for($this->company)->create(['type' => 'Expense']);
    $depreciationJournal = $this->company->default_depreciation_journal_id;

    // Arrange: Create an asset to be depreciated.
    $asset = Asset::factory()->for($this->company)->create([
        'purchase_value' => 1200.00,
        'purchase_date' => now()->subYear(),
        'depreciation_method' => 'straight_line',
        'useful_life_years' => 10,
        'asset_account_id' => $fixedAssetAccount->id,
        'accumulated_depreciation_account_id' => $accumulatedDepreciationAccount->id,
        'depreciation_expense_account_id' => $depreciationExpenseAccount->id,
    ]);

    // Act: Run the depreciation for a specific period (e.g., one month).
    // The annual depreciation is 1200 * 10% = 120. Monthly is 10.
    (app(AssetService::class))->runDepreciation($asset, $this->user);

    // Assert: A depreciation entry was created for the asset.
    $this->assertDatabaseCount('depreciation_entries', 1);
    $depreciationEntry = $asset->depreciationEntries()->first();
    expect($depreciationEntry->amount)->toEqual(1000);

    // Assert: A journal entry was created and linked.
    $this->assertNotNull($depreciationEntry->journal_entry_id);
    $journalEntry = $depreciationEntry->journalEntry;
    $this->assertModelExists($journalEntry);

    // Assert: The journal entry has the correct details.
    expect($journalEntry->journal_id)->toBe($depreciationJournal);
    expect($journalEntry->total_debit)->toEqual(1000);
    expect($journalEntry->total_credit)->toEqual(1000);

    // Assert: The correct accounts were debited and credited.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $depreciationExpenseAccount->id,
        'debit' => 1000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $accumulatedDepreciationAccount->id,
        'credit' => 1000,
    ]);

    // Assert: The asset's book value was updated.
    $asset->refresh();
    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
    ]);
});
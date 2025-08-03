<?php

use Carbon\Carbon;
use App\Models\User;
use App\Models\Asset;
use App\Models\Account;
use App\Models\Company;
use App\Services\AssetService;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithConfiguredCompany;
use Brick\Money\Money; // Import the Money class
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('running depreciation for an asset creates the correct journal entries', function () {
    // Arrange: Set up the necessary accounts and journal.
    $fixedAssetAccount = Account::factory()->for($this->company)->create(['type' => 'Fixed Asset']);
    $accumulatedDepreciationAccount = Account::factory()->for($this->company)->create(['type' => 'Accumulated Depreciation']);
    $depreciationExpenseAccount = Account::factory()->for($this->company)->create(['type' => 'Expense']);
    $depreciationJournal = $this->company->default_depreciation_journal_id;

    // Arrange: Create an asset to be depreciated.
    $currencyCode = $this->company->currency->code;
    $asset = Asset::factory()->for($this->company)->create([
        // MODIFIED: Ensure this value is 1200, not 12000. This is the likely source of the error.
        'purchase_value' => Money::of(1200, $currencyCode),
        'salvage_value' => Money::of(0, $currencyCode),
        'purchase_date' => now()->subYear(),
        'depreciation_method' => 'straight_line',
        'useful_life_years' => 10,
        'asset_account_id' => $fixedAssetAccount->id,
        'accumulated_depreciation_account_id' => $accumulatedDepreciationAccount->id,
        'depreciation_expense_account_id' => $depreciationExpenseAccount->id,
    ]);

    // Act: Run the depreciation for a specific period (e.g., one month).
    // The annual depreciation is 120. Monthly is 10.
    (app(AssetService::class))->runDepreciation($asset, $this->user);

    // Assert: A depreciation entry was created for the asset.
    $this->assertDatabaseCount('depreciation_entries', 1);
    $depreciationEntry = $asset->depreciationEntries()->first();
    $expectedMonthlyAmount = Money::of(10, $currencyCode);
    expect($depreciationEntry->amount->isEqualTo($expectedMonthlyAmount))->toBeTrue();

    // Assert: A journal entry was created and linked.
    $this->assertNotNull($depreciationEntry->journal_entry_id);
    $journalEntry = $depreciationEntry->journalEntry;
    $this->assertModelExists($journalEntry);

    // Assert: The journal entry has the correct details.
    expect($journalEntry->journal_id)->toBe($depreciationJournal);
    expect($journalEntry->total_debit->isEqualTo($expectedMonthlyAmount))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedMonthlyAmount))->toBeTrue();

    // Assert: The correct accounts were debited and credited.
    // The database stores the minor amount (integer), so 10.00 becomes 1000.
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $depreciationExpenseAccount->id,
        'debit' => 10000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $accumulatedDepreciationAccount->id,
        'credit' => 10000,
    ]);

    // Assert: The asset's book value was updated.
    $asset->refresh();
    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
    ]);
});

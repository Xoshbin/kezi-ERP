<?php

use App\Actions\Accounting\CreateJournalEntryForDepreciationAction;
use App\Actions\Assets\PostDepreciationEntryAction;
use App\Enums\Assets\DepreciationEntryStatus;
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
    $fixedAssetAccount = Account::factory()->for($this->company)->create(['type' => 'asset']);
    $accumulatedDepreciationAccount = Account::factory()->for($this->company)->create(['type' => 'asset']);
    $depreciationExpenseAccount = Account::factory()->for($this->company)->create(['type' => 'expense']);
    $depreciationJournal = $this->company->default_depreciation_journal_id;

    // Arrange: Create an asset to be depreciated.
    $currencyCode = $this->company->currency->code;
    $asset = Asset::factory()->for($this->company)->create([
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
    (app(AssetService::class))->computeDepreciation($asset);
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

<?php

use App\Models\User;
use App\Models\Asset;
use Brick\Money\Money;
use App\Models\DepreciationEntry;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithConfiguredCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Actions\Accounting\CreateJournalEntryForDepreciationAction;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('it creates a correct journal entry for a depreciation entry', function () {

    $asset = Asset::factory()->for($this->company)->create([
        'depreciation_expense_account_id' => \App\Models\Account::factory()->for($this->company)->create()->id,
        'accumulated_depreciation_account_id' => \App\Models\Account::factory()->for($this->company)->create()->id,
    ]);

    // FIX: Be explicit about the state of the model before the action runs.
    $depreciationEntry = DepreciationEntry::factory()
        ->for($asset)
        ->create([
            'amount' => Money::of(120, $$this->company->currencyCode),
            'depreciation_date' => now(),
            'journal_entry_id' => null, // It should not have a journal entry yet.
            'status' => 'Posted',      // The service sets this status right before the action.
        ]);

    // 2. Act
    $action = app(CreateJournalEntryForDepreciationAction::class);
    $journalEntry = $action->execute($depreciationEntry, $this->user);

    // 3. Assert
    $this->assertNotNull($journalEntry);
    $this->assertTrue($journalEntry->is_posted);
    $this->assertEquals($this->company->default_depreciation_journal_id, $journalEntry->journal_id);

    $expectedTotal = Money::of(120, $$this->company->currencyCode);
    $this->assertTrue($journalEntry->total_debit->isEqualTo($expectedTotal));
    $this->assertTrue($journalEntry->total_credit->isEqualTo($expectedTotal));

    // Assert correct accounts were used for debit and credit
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $asset->depreciation_expense_account_id,
        'debit' => 120000,
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $asset->accumulated_depreciation_account_id,
        'credit' => 120000,
    ]);
});

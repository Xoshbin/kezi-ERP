<?php

namespace Modules\Accounting\Tests\Feature\Services;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\JournalEntryService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('autogenerates entry_number when posting a journal entry', function () {
    // 1. Create a draft journal entry (entry_number should be null)
    /** @var JournalEntry $journalEntry */
    $journalEntry = JournalEntry::factory()
        ->for($this->company)
        ->create([
            'is_posted' => false,
            'entry_number' => null,
            'reference' => 'Test Reference', // Optional reference
        ]);

    // Create balanced lines
    $accountA = Account::factory()->for($this->company)->create();
    $accountB = Account::factory()->for($this->company)->create();

    // We need to attach lines to the journal entry to make it postable (balanced)
    $journalEntry->lines()->create([
        'company_id' => $this->company->id,
        'account_id' => $accountA->id,
        'debit' => Money::of(100, $this->company->currency->code),
        'credit' => Money::of(0, $this->company->currency->code),
        'description' => 'Line 1',
        'partner_id' => null,
        'analytic_account_id' => null,
        'journal_entry_id' => $journalEntry->id,
        'currency_id' => $this->company->currency_id,
        'original_currency_id' => $this->company->currency_id,
        'exchange_rate_at_transaction' => 1.0,
        'original_currency_amount' => Money::of(100, $this->company->currency->code),
    ]);
    $journalEntry->lines()->create([
        'company_id' => $this->company->id,
        'account_id' => $accountB->id,
        'debit' => Money::of(0, $this->company->currency->code),
        'credit' => Money::of(100, $this->company->currency->code),
        'description' => 'Line 2',
        'partner_id' => null,
        'analytic_account_id' => null,
        'journal_entry_id' => $journalEntry->id,
        'currency_id' => $this->company->currency_id,
        'original_currency_id' => $this->company->currency_id,
        'exchange_rate_at_transaction' => 1.0,
        'original_currency_amount' => Money::of(100, $this->company->currency->code),
    ]);

    expect($journalEntry->entry_number)->toBeNull()
        ->and($journalEntry->is_posted)->toBeFalse();

    // 2. Post the entry using JournalEntryService
    $service = app(JournalEntryService::class);
    $service->post($journalEntry);

    // 3. Verify entry_number is generated
    expect($journalEntry->fresh())
        ->is_posted->toBeTrue()
        ->entry_number->not->toBeNull()
        // We expect some format like "JE-00001" or similar depending on Sequence defaults
        ->entry_number->toContain('JE');
});

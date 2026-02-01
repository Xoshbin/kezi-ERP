<?php

namespace Kezi\Accounting\Tests\Feature\Contracts;

use Kezi\Accounting\Contracts\JournalEntryCreatorContract;

it('binds JournalEntryCreatorContract to CreateJournalEntryAction', function () {
    $resolved = app(JournalEntryCreatorContract::class);

    expect($resolved)
        ->toBeInstanceOf(\Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction::class);
});

it('can create a journal entry via the contract', function () {
    $company = \App\Models\Company::factory()->create();
    $user = \App\Models\User::factory()->create();
    $journal = \Kezi\Accounting\Models\Journal::factory()->for($company)->create();
    $currency = \Kezi\Foundation\Models\Currency::factory()->createSafely(['code' => 'USD']);

    $account1 = \Kezi\Accounting\Models\Account::factory()->for($company)->create();
    $account2 = \Kezi\Accounting\Models\Account::factory()->for($company)->create();

    $dto = new \Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO(
        company_id: $company->id,
        journal_id: $journal->id,
        currency_id: $currency->id,
        entry_date: now()->toDateString(),
        reference: 'TEST-CONTRACT-001',
        description: 'Test journal entry via contract',
        created_by_user_id: $user->id,
        is_posted: false,
        lines: [
            new \Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO(
                account_id: $account1->id,
                debit: \Brick\Money\Money::of(1000, 'USD'),
                credit: \Brick\Money\Money::zero('USD'),
                description: 'Debit line',
                partner_id: null,
                analytic_account_id: null
            ),
            new \Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO(
                account_id: $account2->id,
                debit: \Brick\Money\Money::zero('USD'),
                credit: \Brick\Money\Money::of(1000, 'USD'),
                description: 'Credit line',
                partner_id: null,
                analytic_account_id: null
            ),
        ]
    );

    $contract = app(JournalEntryCreatorContract::class);
    $journalEntry = $contract->execute($dto);

    expect($journalEntry)
        ->toBeInstanceOf(\Kezi\Accounting\Models\JournalEntry::class)
        ->reference->toBe('TEST-CONTRACT-001');

    expect($journalEntry->lines)->toHaveCount(2);
});

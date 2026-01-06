<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Journal;
use Modules\Payment\Actions\Cheques\RegisterBounceAction;
use Modules\Payment\DataTransferObjects\Cheques\BounceChequeDTO;
use Modules\Payment\Enums\Cheques\ChequeStatus;
use Modules\Payment\Models\Cheque;
use Modules\Payment\Models\ChequeBouncedLog;

uses(RefreshDatabase::class);

it('can register a bounce for deposited cheque', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    $user->refresh();

    // Accounts for reversal (RegisterBounceAction logic reverses journal entries)
    // We need to ensure original JE exists or can be reversed.
    // Actually RegisterBounceAction usually inspects the PREVIOUS JE to reverse it.
    // Or it generates a new JE based on context.

    // For this test, we might mock the JE or ensure logic handles it.
    // Detailed logic: RegisterBounceAction likely reverses the *current* journal_entry_id linked to Cheque.
    $journalEntry = \Modules\Accounting\Models\JournalEntry::factory()->create(['company_id' => $company->id]);
    $journalEntry->is_posted = true;
    $journalEntry->saveQuietly();

    $cheque = Cheque::factory()->receivable()->create([
        'company_id' => $company->id,
        'status' => ChequeStatus::Deposited,
        'amount' => 50000,
        'currency_id' => $company->currency_id,
        'journal_entry_id' => $journalEntry->id,
        'partner_id' => \Modules\Foundation\Models\Partner::factory()->create(['company_id' => $company->id])->id,
    ]);

    // Create Lines for the JE to reverse (Must be balanced)
    \Modules\Accounting\Models\JournalEntryLine::factory()->create([
        'journal_entry_id' => $cheque->journal_entry_id,
        'company_id' => $company->id,
        'debit' => Money::of(50000, 'IQD'),
        'credit' => Money::zero('IQD'),
    ]);
    \Modules\Accounting\Models\JournalEntryLine::factory()->create([
        'journal_entry_id' => $cheque->journal_entry_id,
        'company_id' => $company->id,
        'debit' => Money::zero('IQD'),
        'credit' => Money::of(50000, 'IQD'),
    ]);

    $dto = new BounceChequeDTO(
        cheque_id: $cheque->id,
        bounced_at: now()->format('Y-m-d'),
        reason: 'Insufficient Funds',
        bank_charges: null,
        notes: 'Customer notified'
    );

    $action = app(RegisterBounceAction::class);
    $action->execute($cheque, $dto, $user);

    expect($cheque->fresh())
        ->status->toBe(ChequeStatus::Bounced)
        ->bounced_at->not->toBeNull();

    expect(ChequeBouncedLog::where('cheque_id', $cheque->id)->exists())->toBeTrue();
});

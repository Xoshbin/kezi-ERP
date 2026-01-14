<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Payment\Actions\Cheques\ClearChequeAction;
use Modules\Payment\DataTransferObjects\Cheques\ClearChequeDTO;
use Modules\Payment\Enums\Cheques\ChequeStatus;
use Modules\Payment\Models\Cheque;

uses(RefreshDatabase::class);

it('can clear a deposited receivable cheque', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    $user->refresh();

    // Accounts
    $bankAccount = Account::factory()->create(['company_id' => $company->id, 'code' => '101000']);
    $pdcReceivable = Account::factory()->create(['company_id' => $company->id, 'code' => '103000']);

    // Create Journal with default account
    $journal = Journal::create([
        'company_id' => $company->id,
        'name' => 'Test Journal 1',
        'type' => \Modules\Accounting\Enums\Accounting\JournalType::Miscellaneous,
        'short_code' => 'TJ1',
        'currency_id' => $company->currency_id,
        'default_account_id' => $bankAccount->id,
        'default_debit_account_id' => $pdcReceivable->id,
        'default_credit_account_id' => $bankAccount->id,
    ]);

    $company->update([
        'default_bank_account_id' => $bankAccount->id,
        'default_pdc_receivable_account_id' => $pdcReceivable->id,
    ]);

    // Create a real JournalEntry to link to the cheque
    $previousJE = JournalEntry::factory()->create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $company->currency_id,
    ]);

    $cheque = Cheque::factory()->receivable()->create([
        'company_id' => $company->id,
        'status' => ChequeStatus::Deposited,
        'amount' => 50000,
        'currency_id' => $company->currency_id,
        'journal_id' => $journal->id,
        'journal_entry_id' => $previousJE->id,
        'partner_id' => \Modules\Foundation\Models\Partner::factory()->create(['company_id' => $company->id])->id,
    ]);

    $dto = new ClearChequeDTO(
        cheque_id: $cheque->id,
        cleared_at: now()->format('Y-m-d')
    );

    $action = app(ClearChequeAction::class);
    $action->execute($cheque, $dto, $user);

    expect($cheque->fresh())
        ->status->toBe(ChequeStatus::Cleared)
        ->cleared_at->not->toBeNull();
});

it('can clear a handed over payable cheque', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    $user->refresh();

    // Accounts
    $bankAccount = Account::factory()->create(['company_id' => $company->id, 'code' => '101000']);
    $outstandingPayable = Account::factory()->create(['company_id' => $company->id, 'code' => '201000']);

    // Create Journal with default account
    $journal = Journal::create([
        'company_id' => $company->id,
        'name' => 'Test Journal 2',
        'type' => \Modules\Accounting\Enums\Accounting\JournalType::Miscellaneous,
        'short_code' => 'TJ2',
        'currency_id' => $company->currency_id,
        'default_account_id' => $bankAccount->id,
        'default_debit_account_id' => $bankAccount->id,
        'default_credit_account_id' => $outstandingPayable->id,
    ]);

    $company->update([
        'default_bank_account_id' => $bankAccount->id,
        'default_pdc_payable_account_id' => $outstandingPayable->id,
    ]);

    // Create a real JournalEntry to link to the cheque
    $previousJE = JournalEntry::factory()->create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'currency_id' => $company->currency_id,
    ]);

    $cheque = Cheque::factory()->payable()->create([
        'company_id' => $company->id,
        'status' => ChequeStatus::HandedOver,
        'amount' => 50000,
        'currency_id' => $company->currency_id,
        'journal_id' => $journal->id,
        'journal_entry_id' => $previousJE->id,
        'partner_id' => \Modules\Foundation\Models\Partner::factory()->create(['company_id' => $company->id])->id,
    ]);

    $dto = new ClearChequeDTO(
        cheque_id: $cheque->id,
        cleared_at: now()->format('Y-m-d')
    );

    $action = app(ClearChequeAction::class);
    $action->execute($cheque, $dto, $user);

    expect($cheque->fresh())
        ->status->toBe(ChequeStatus::Cleared)
        ->cleared_at->not->toBeNull();
});

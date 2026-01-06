<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Payment\Actions\Cheques\HandOverChequeAction;
use Modules\Payment\Enums\Cheques\ChequeStatus;
use Modules\Payment\Models\Cheque;

uses(RefreshDatabase::class);

it('can hand over a payable cheque and creates journal entry', function () {
    $company = \App\Models\Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    $user->refresh();

    // Setup accounts for JE
    $bankAccount = Account::factory()->create(['company_id' => $company->id, 'code' => '101000']);
    $outstandingCheckAccount = Account::factory()->create(['company_id' => $company->id, 'code' => '201000']); // Liability
    $apAccount = Account::factory()->create(['company_id' => $company->id, 'code' => '200000']);

    $company->update([
        'default_bank_account_id' => $bankAccount->id,
        'default_pdc_payable_account_id' => $outstandingCheckAccount->id,
        'default_accounts_payable_id' => $apAccount->id,
    ]);

    $journal = Journal::factory()->create(['company_id' => $company->id]);

    $cheque = Cheque::factory()->payable()->create([
        'company_id' => $company->id,
        'journal_id' => $journal->id,
        'status' => ChequeStatus::Printed, // Must be printed first
        'amount' => 1000,
        'currency_id' => $company->currency_id,
    ]);

    $action = app(HandOverChequeAction::class);
    $action->execute($cheque, $user);

    expect($cheque->fresh())
        ->status->toBe(ChequeStatus::HandedOver)
        ->journal_entry_id->not->toBeNull();
});

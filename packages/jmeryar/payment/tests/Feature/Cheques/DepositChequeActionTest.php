<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Payment\Actions\Cheques\DepositChequeAction;
use Jmeryar\Payment\DataTransferObjects\Cheques\DepositChequeDTO;
use Jmeryar\Payment\Enums\Cheques\ChequeStatus;
use Jmeryar\Payment\Models\Cheque;

uses(RefreshDatabase::class);

it('can deposit a receivable cheque', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    $user->refresh();

    // Accounts
    $pdcReceivable = Account::factory()->create(['company_id' => $company->id, 'code' => '102000']);
    $arAccount = Account::factory()->create(['company_id' => $company->id, 'code' => '101000']);

    $company->update([
        'default_pdc_receivable_account_id' => $pdcReceivable->id,
        'default_accounts_receivable_id' => $arAccount->id,
    ]);

    $cheque = Cheque::factory()->receivable()->create([
        'company_id' => $company->id,
        'status' => ChequeStatus::Draft,
        'amount' => 50000,
        'currency_id' => $company->currency_id,
        'partner_id' => \Jmeryar\Foundation\Models\Partner::factory()->create(['company_id' => $company->id])->id,
    ]);

    $dto = new DepositChequeDTO(
        cheque_id: $cheque->id,
        deposited_at: now()->format('Y-m-d')
    );

    $action = app(DepositChequeAction::class);
    $action->execute($cheque, $dto, $user);

    expect($cheque->fresh())
        ->status->toBe(ChequeStatus::Deposited)
        ->deposited_at->not->toBeNull()
        ->journal_entry_id->not->toBeNull();
});

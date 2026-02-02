<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Models\Currency;
use Kezi\Payment\Actions\PettyCash\CreatePettyCashFundAction;
use Kezi\Payment\DataTransferObjects\PettyCash\CreatePettyCashFundDTO;
use Kezi\Payment\Enums\PettyCash\PettyCashFundStatus;
use Kezi\Payment\Models\PettyCash\PettyCashFund;

test('can create petty cash fund', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $custodian = User::factory()->create();

    $currency = Currency::firstOrCreate(
        ['code' => 'IQD'],
        [
            'name' => ['en' => 'Iraqi Dinar'],
            'symbol' => 'د.ع',
            'decimal_places' => 3,
            'is_active' => true,
        ]
    );

    $pettyCashAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Petty Cash',
        'code' => '1010',
    ]);

    $bankAccount = Account::factory()->create([
        'company_id' => $company->id,
        'name' => 'Bank',
        'code' => '1001',
    ]);

    $dto = new CreatePettyCashFundDTO(
        company_id: $company->id,
        name: 'Main Office Petty Cash',
        custodian_id: $custodian->id,
        account_id: $pettyCashAccount->id,
        bank_account_id: $bankAccount->id,
        currency_id: $currency->id,
        imprest_amount: Money::of(500000, 'IQD'),
    );

    $action = app(CreatePettyCashFundAction::class);
    $fund = $action->execute($dto, $user);

    expect($fund)
        ->toBeInstanceOf(PettyCashFund::class)
        ->name->toBe('Main Office Petty Cash')
        ->status->toBe(PettyCashFundStatus::Active)
        ->imprest_amount->isEqualTo(Money::of(500000, 'IQD'))->toBeTrue()
        ->current_balance->isEqualTo(Money::of(500000, 'IQD'))->toBeTrue();
});

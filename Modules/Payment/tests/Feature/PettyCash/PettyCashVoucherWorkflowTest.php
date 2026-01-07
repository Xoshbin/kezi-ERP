<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Currency;
use Modules\Payment\Actions\PettyCash\CreatePettyCashVoucherAction;
use Modules\Payment\Actions\PettyCash\PostPettyCashVoucherAction;
use Modules\Payment\DataTransferObjects\PettyCash\CreatePettyCashVoucherDTO;
use Modules\Payment\Enums\PettyCash\PettyCashVoucherStatus;
use Modules\Payment\Models\PettyCash\PettyCashFund;
use Modules\Payment\Models\PettyCash\PettyCashVoucher;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();

    $currency = Currency::firstOrCreate(
        ['code' => 'IQD'],
        [
            'name' => ['en' => 'Iraqi Dinar'],
            'symbol' => 'د.ع',
            'decimal_places' => 3,
            'is_active' => true,
        ]
    );

    // Create and configure cash journal BEFORE creating fund
    $cashJournal = \Modules\Accounting\Models\Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'cash',
        'name' => ['en' => 'Cash Journal'],
    ]);

    $this->company->update(['default_cash_journal_id' => $cashJournal->id]);
    $this->company->refresh(); // Ensure the company instance has the updated value

    $pettyCashAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1010',
    ]);

    $bankAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1001',
    ]);

    $this->fund = PettyCashFund::create([
        'company_id' => $this->company->id,
        'name' => 'Test Fund',
        'custodian_id' => $this->user->id,
        'account_id' => $pettyCashAccount->id,
        'bank_account_id' => $bankAccount->id,
        'currency_id' => $currency->id,
        'imprest_amount' => Money::of(500000, 'IQD'),
        'current_balance' => Money::of(500000, 'IQD'),
        'status' => 'active',
    ]);

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '5001',
        'name' => 'Office Supplies',
    ]);
});

test('can create petty cash voucher', function () {
    $dto = new CreatePettyCashVoucherDTO(
        company_id: $this->company->id,
        fund_id: $this->fund->id,
        expense_account_id: $this->expenseAccount->id,
        amount: Money::of(25000, 'IQD'),
        voucher_date: Carbon::now()->format('Y-m-d'),
        description: 'Printer paper',
    );

    $action = app(CreatePettyCashVoucherAction::class);
    $voucher = $action->execute($dto, $this->user);

    expect($voucher)
        ->toBeInstanceOf(PettyCashVoucher::class)
        ->status->toBe(PettyCashVoucherStatus::Draft)
        ->amount->isEqualTo(Money::of(25000, 'IQD'))->toBeTrue()
        ->description->toBe('Printer paper');
});

test('can post petty cash voucher and update fund balance', function () {
    $voucher = PettyCashVoucher::create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
        'voucher_number' => 'PCV-001',
        'expense_account_id' => $this->expenseAccount->id,
        'amount' => Money::of(25000, 'IQD'),
        'voucher_date' => Carbon::now(),
        'description' => 'Office supplies',
        'status' => PettyCashVoucherStatus::Draft,
    ]);

    $action = app(PostPettyCashVoucherAction::class);
    $journalEntry = $action->execute($voucher, $this->user);

    expect($voucher->fresh())
        ->status->toBe(PettyCashVoucherStatus::Posted)
        ->journal_entry_id->not->toBeNull();

    expect($this->fund->fresh())
        ->current_balance->isEqualTo(Money::of(475000, 'IQD'))->toBeTrue();

    expect($journalEntry)->not->toBeNull();
});

test('cannot post voucher exceeding fund balance', function () {
    $voucher = PettyCashVoucher::create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
        'voucher_number' => 'PCV-002',
        'expense_account_id' => $this->expenseAccount->id,
        'amount' => Money::of(600000, 'IQD'), // More than fund balance
        'voucher_date' => Carbon::now(),
        'description' => 'Large expense',
        'status' => PettyCashVoucherStatus::Draft,
    ]);

    $action = app(PostPettyCashVoucherAction::class);

    $action->execute($voucher, $this->user);
})->throws(\InvalidArgumentException::class, 'Insufficient petty cash balance');

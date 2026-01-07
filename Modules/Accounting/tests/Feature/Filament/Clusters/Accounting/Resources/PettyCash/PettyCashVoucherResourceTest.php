<?php

namespace Modules\Accounting\Tests\Feature\Filament\Clusters\Accounting\Resources\PettyCash;

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource\Pages\ListPettyCashVouchers;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Currency;
use Modules\Payment\Enums\PettyCash\PettyCashVoucherStatus;
use Modules\Payment\Models\PettyCash\PettyCashFund;
use Modules\Payment\Models\PettyCash\PettyCashVoucher;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->user->refresh();

    $this->actingAs($this->user);
    Filament::setTenant($this->company);

    // Setup currency
    $this->currency = Currency::firstOrCreate(
        ['code' => 'IQD'],
        [
            'name' => ['en' => 'Iraqi Dinar'],
            'symbol' => 'د.ع',
            'decimal_places' => 3,
            'is_active' => true,
        ]
    );

    // Setup accounts
    $pettyCashAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1010',
    ]);

    $bankAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1001',
    ]);

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '5001',
        'name' => 'Office Supplies',
    ]);

    $this->custodian = User::factory()->create();

    // Setup fund
    $this->fund = PettyCashFund::factory()->create([
        'company_id' => $this->company->id,
        'custodian_id' => $this->custodian->id,
        'account_id' => $pettyCashAccount->id,
        'bank_account_id' => $bankAccount->id,
        'currency_id' => $this->currency->id,
    ]);
});

it('can render the petty cash vouchers list page', function () {
    $vouchers = PettyCashVoucher::factory()->count(5)->create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    livewire(ListPettyCashVouchers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($vouchers);
});

it('can filter vouchers by status', function () {
    $draftVoucher = PettyCashVoucher::factory()->create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
        'expense_account_id' => $this->expenseAccount->id,
        'status' => PettyCashVoucherStatus::Draft,
    ]);

    $postedVoucher = PettyCashVoucher::factory()->create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
        'expense_account_id' => $this->expenseAccount->id,
        'status' => PettyCashVoucherStatus::Posted,
    ]);

    livewire(ListPettyCashVouchers::class)
        ->filterTable('status', PettyCashVoucherStatus::Draft->value)
        ->assertCanSeeTableRecords([$draftVoucher])
        ->assertCanNotSeeTableRecords([$postedVoucher]);
});

it('can filter vouchers by fund', function () {
    $voucher1 = PettyCashVoucher::factory()->create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    // Create another fund
    $otherFund = PettyCashFund::factory()->create([
        'company_id' => $this->company->id,
        'custodian_id' => $this->custodian->id,
        'account_id' => $this->fund->account_id,
        'bank_account_id' => $this->fund->bank_account_id,
        'currency_id' => $this->currency->id,
    ]);

    $voucher2 = PettyCashVoucher::factory()->create([
        'company_id' => $this->company->id,
        'fund_id' => $otherFund->id,
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    livewire(ListPettyCashVouchers::class)
        ->filterTable('fund_id', $this->fund->id)
        ->assertCanSeeTableRecords([$voucher1])
        ->assertCanNotSeeTableRecords([$voucher2]);
});

it('can search vouchers by description', function () {
    $voucher1 = PettyCashVoucher::factory()->create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
        'expense_account_id' => $this->expenseAccount->id,
        'voucher_number' => 'PCV-1001',
        'description' => 'Unique office purchase',
    ]);

    $voucher2 = PettyCashVoucher::factory()->create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
        'expense_account_id' => $this->expenseAccount->id,
        'voucher_number' => 'PCV-1002',
        'description' => 'Taxi fare payment',
    ]);

    livewire(ListPettyCashVouchers::class)
        ->searchTable('PCV-1001')
        ->assertCanSeeTableRecords([$voucher1])
        ->assertCanNotSeeTableRecords([$voucher2]);
});

<?php

namespace Modules\Accounting\Tests\Feature\Filament\Clusters\Accounting\Resources\PettyCash;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashFundResource\Pages\ListPettyCashFunds;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Currency;
use Modules\Payment\Enums\PettyCash\PettyCashFundStatus;
use Modules\Payment\Models\PettyCash\PettyCashFund;

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
    $this->pettyCashAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1010',
        'name' => 'Petty Cash',
    ]);

    $this->bankAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1001',
        'name' => 'Bank',
    ]);

    $this->custodian = User::factory()->create();
});

it('can render the petty cash funds list page', function () {
    $funds = PettyCashFund::factory()->count(5)->create([
        'company_id' => $this->company->id,
        'custodian_id' => $this->custodian->id,
        'account_id' => $this->pettyCashAccount->id,
        'bank_account_id' => $this->bankAccount->id,
        'currency_id' => $this->currency->id,
    ]);

    livewire(ListPettyCashFunds::class)
        ->assertOk()
        ->assertCanSeeTableRecords($funds);
});

it('can filter funds by status', function () {
    $activeFund = PettyCashFund::factory()->create([
        'company_id' => $this->company->id,
        'custodian_id' => $this->custodian->id,
        'account_id' => $this->pettyCashAccount->id,
        'bank_account_id' => $this->bankAccount->id,
        'currency_id' => $this->currency->id,
        'status' => PettyCashFundStatus::Active,
    ]);

    $closedFund = PettyCashFund::factory()->create([
        'company_id' => $this->company->id,
        'custodian_id' => $this->custodian->id,
        'account_id' => $this->pettyCashAccount->id,
        'bank_account_id' => $this->bankAccount->id,
        'currency_id' => $this->currency->id,
        'status' => PettyCashFundStatus::Closed,
        'current_balance' => Money::zero('IQD'),
    ]);

    livewire(ListPettyCashFunds::class)
        ->filterTable('status', PettyCashFundStatus::Active->value)
        ->assertCanSeeTableRecords([$activeFund])
        ->assertCanNotSeeTableRecords([$closedFund]);
});

it('can search funds by name', function () {
    $fund1 = PettyCashFund::factory()->create([
        'company_id' => $this->company->id,
        'custodian_id' => $this->custodian->id,
        'account_id' => $this->pettyCashAccount->id,
        'bank_account_id' => $this->bankAccount->id,
        'currency_id' => $this->currency->id,
        'name' => 'Main Office Fund',
    ]);

    $fund2 = PettyCashFund::factory()->create([
        'company_id' => $this->company->id,
        'custodian_id' => $this->custodian->id,
        'account_id' => $this->pettyCashAccount->id,
        'bank_account_id' => $this->bankAccount->id,
        'currency_id' => $this->currency->id,
        'name' => 'Branch Office Fund',
    ]);

    livewire(ListPettyCashFunds::class)
        ->searchTable('Main Office')
        ->assertCanSeeTableRecords([$fund1])
        ->assertCanNotSeeTableRecords([$fund2]);
});

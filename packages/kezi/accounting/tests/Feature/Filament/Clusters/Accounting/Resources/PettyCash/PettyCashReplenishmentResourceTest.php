<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Clusters\Accounting\Resources\PettyCash;

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashReplenishmentResource\Pages\ListPettyCashReplenishments;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Models\Currency;
use Kezi\Payment\Models\PettyCash\PettyCashFund;
use Kezi\Payment\Models\PettyCash\PettyCashReplenishment;

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

it('can render the petty cash replenishments list page', function () {
    $replenishments = PettyCashReplenishment::factory()->count(5)->create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
    ]);

    livewire(ListPettyCashReplenishments::class)
        ->assertOk()
        ->assertCanSeeTableRecords($replenishments);
});

it('can filter replenishments by fund', function () {
    $replenishment1 = PettyCashReplenishment::factory()->create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
    ]);

    // Create another fund
    $otherFund = PettyCashFund::factory()->create([
        'company_id' => $this->company->id,
        'custodian_id' => $this->custodian->id,
        'account_id' => $this->fund->account_id,
        'bank_account_id' => $this->fund->bank_account_id,
        'currency_id' => $this->currency->id,
    ]);

    $replenishment2 = PettyCashReplenishment::factory()->create([
        'company_id' => $this->company->id,
        'fund_id' => $otherFund->id,
    ]);

    livewire(ListPettyCashReplenishments::class)
        ->filterTable('fund_id', $this->fund->id)
        ->assertCanSeeTableRecords([$replenishment1])
        ->assertCanNotSeeTableRecords([$replenishment2]);
});

it('can search replenishments by reference', function () {
    $replenishment1 = PettyCashReplenishment::factory()->create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
        'reference' => 'TRX-12345',
    ]);

    $replenishment2 = PettyCashReplenishment::factory()->create([
        'company_id' => $this->company->id,
        'fund_id' => $this->fund->id,
        'reference' => 'TRX-67890',
    ]);

    livewire(ListPettyCashReplenishments::class)
        ->searchTable('TRX-12345')
        ->assertCanSeeTableRecords([$replenishment1])
        ->assertCanNotSeeTableRecords([$replenishment2]);
});

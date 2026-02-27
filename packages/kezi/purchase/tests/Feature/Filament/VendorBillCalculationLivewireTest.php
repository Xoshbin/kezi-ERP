<?php

namespace Kezi\Purchase\Tests\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Database\Seeders\RolesAndPermissionsSeeder;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Livewire\Livewire;

uses(RefreshDatabase::class);
uses(\Tests\Traits\WithSuperAdminRole::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->user->companies()->attach($this->company);

    setPermissionsTeamId($this->company->id);
    $this->assignSuperAdminRole($this->user, $this->company);

    $this->actingAs($this->user);
    \Filament\Facades\Filament::setTenant($this->company);

    $this->iqd = Currency::factory()->createSafely(['code' => 'IQD', 'decimal_places' => 0]);
    $this->usd = Currency::factory()->createSafely(['code' => 'USD', 'decimal_places' => 2]);

    $this->company->update(['currency_id' => $this->iqd->id]);

    $this->vendor = Partner::factory()->create(['company_id' => $this->company->id, 'type' => 'vendor']);

    $this->expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense,
    ]);

    $this->product1 = Product::factory()->create(['company_id' => $this->company->id, 'expense_account_id' => $this->expenseAccount->id]);
    $this->product2 = Product::factory()->create(['company_id' => $this->company->id, 'expense_account_id' => $this->expenseAccount->id]);
    $this->product3 = Product::factory()->create(['company_id' => $this->company->id, 'expense_account_id' => $this->expenseAccount->id]);
});

test('vendor bill create form calculates totals correctly for base and local currency', function () {
    Livewire::test(CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->usd->id,
            'exchange_rate_at_creation' => 1250,
            'lines' => [
                [
                    'product_id' => $this->product1->id,
                    'description' => 'Item 1',
                    'quantity' => 10,
                    'unit_price' => 1300, // Filament sets this as 1300 or 1300.00
                    'expense_account_id' => $this->expenseAccount->id,
                ],
                [
                    'product_id' => $this->product2->id,
                    'description' => 'Item 2',
                    'quantity' => 50,
                    'unit_price' => 140, // Filament sets this as 140 or 140.00
                    'expense_account_id' => $this->expenseAccount->id,
                ],
                [
                    'product_id' => $this->product3->id,
                    'description' => 'Item 3',
                    'quantity' => 35,
                    'unit_price' => 300, // Filament sets this as 300 or 300.00
                    'expense_account_id' => $this->expenseAccount->id,
                ],
            ],
        ])
        ->assertSee('30,500.00') // USD total
        ->assertSee('38,125,000'); // IQD total
});

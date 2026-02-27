<?php

namespace Kezi\Purchase\Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Database\Seeders\RolesAndPermissionsSeeder;
use Kezi\Foundation\Models\Currency;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillLine;

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

    // Setup currencies
    $this->iqd = Currency::factory()->createSafely(['code' => 'IQD', 'decimal_places' => 3]);
    $this->usd = Currency::factory()->createSafely(['code' => 'USD', 'decimal_places' => 2]);

    $this->company->update(['currency_id' => $this->iqd->id]);
});

test('vendor bill calculation matches exact scenario for USD to local currency', function () {
    // Exact scenario:
    // Exchange Rate: 1250
    // Line Item 1: Qty 10 @ $1,300.00 = $13,000.00
    // Line Item 2: Qty 50 @ $140.00 = $7,000.00
    // Line Item 3: Qty 35 @ $300.00 = $10,500.00
    // EXPECTED Base Currency Total (USD): $30,500.00
    // EXPECTED Local Currency Total: 38,125,000.00 (which is 30,500 * 1250)

    $bill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->usd->id,
        'exchange_rate_at_creation' => 1250,
    ]);

    VendorBillLine::factory()->create([
        'vendor_bill_id' => $bill->id,
        'quantity' => 10,
        'unit_price' => 1300,
        'subtotal' => 13000,
        'total_line_tax' => 0,
    ]);

    VendorBillLine::factory()->create([
        'vendor_bill_id' => $bill->id,
        'quantity' => 50,
        'unit_price' => 140,
        'subtotal' => 7000,
        'total_line_tax' => 0,
    ]);

    VendorBillLine::factory()->create([
        'vendor_bill_id' => $bill->id,
        'quantity' => 35,
        'unit_price' => 300,
        'subtotal' => 10500,
        'total_line_tax' => 0,
    ]);

    $bill->refresh();

    // total_amount is stored in cents, so $30,500.00 is 3050000 cents
    expect($bill->total_amount->getMinorAmount()->toInt())->toBe(3050000, 'Total USD should be 3050000 cents ($30,500)');

    // Local currency amounts are stored in cents too
    // In IQD with 0 decimal places, major and minor amount is the same effectively,
    // Wait, the cast handles it based on Currency object.
    // Let's just check the major amount string.
    expect((string) $bill->total_amount->getAmount())->toBe('30500.00', 'Total USD major amount should be 30500.00');
    expect($bill->total_amount_company_currency->getMinorAmount()->toInt())->toBe(38125000000, 'Total Local Currency minor amount should be 38125000000');
    expect((string) $bill->total_amount_company_currency->getAmount())->toBe('38125000.000', 'Total Local Currency major amount should be 38125000.000');
});

<?php

use App\Models\Tax;
use App\Models\Company;
use App\Models\Account;
use App\Enums\Accounting\TaxType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores tax rate with decimal precision', function () {
    $company = Company::factory()->create();
    $account = Account::factory()->create(['company_id' => $company->id]);

    $tax = Tax::create([
        'company_id' => $company->id,
        'tax_account_id' => $account->id,
        'name' => 'Test Tax 12.5%',
        'rate' => 0.125, // 12.5%
        'type' => TaxType::Sales,
        'is_active' => true
    ]);

    $freshTax = Tax::find($tax->id);

    // Check if it's close enough (float comparison)
    // 0.125 should be exact, but float comparison is safer
    expect(abs($freshTax->rate - 0.125))->toBeLessThan(0.0001);
});

<?php

namespace Modules\Accounting\Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\TaxType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Tax;
use Tests\TestCase;

class TaxRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_can_store_decimal_rate()
    {
        $company = Company::factory()->create();
        $account = Account::factory()->create();

        $tax = Tax::create([
            'company_id' => $company->id,
            'tax_account_id' => $account->id,
            'name' => ['en' => 'VAT 15%'],
            'rate' => 0.15000,
            'type' => TaxType::Sales,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('taxes', [
            'id' => $tax->id,
            'rate' => 0.15000,
        ]);

        $this->assertEquals(0.15, $tax->rate);
        $this->assertEquals(0.15, $tax->rate_fraction);
        $this->assertEquals(15.00, $tax->rate_percentage);
    }
}

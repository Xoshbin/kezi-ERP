<?php

namespace Modules\Sales\Tests\Feature;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\TaxType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Tax;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceLine;
use Tests\TestCase;

class InvoiceLineCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_line_calculates_subtotal_and_tax_on_save()
    {
        // Setup
        $company = Company::factory()->create();
        $currency = $company->currency;

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'currency_id' => $currency->id,
        ]);

        $account = Account::factory()->create();

        $tax = Tax::create([
            'company_id' => $company->id,
            'tax_account_id' => $account->id,
            'name' => ['en' => 'VAT 15%'],
            'rate' => 0.15, // 15%
            'type' => TaxType::Sales,
        ]);

        // Create Line
        $line = new InvoiceLine;
        $line->invoice_id = $invoice->id;
        $line->company_id = $company->id;
        $line->income_account_id = $account->id;
        $line->quantity = 2;
        $line->unit_price = Money::of(100, $currency->code); // 100.00
        $line->tax_id = $tax->id;
        $line->description = 'Test Item';

        // Save
        $line->save();

        // Verify
        $this->assertNotNull($line->subtotal);
        $this->assertTrue($line->subtotal->isEqualTo(Money::of(200, $currency->code))); // 2 * 100

        $this->assertNotNull($line->total_line_tax);
        // 200 * 0.15 = 30
        $this->assertTrue($line->total_line_tax->isEqualTo(Money::of(30, $currency->code)));
    }
}

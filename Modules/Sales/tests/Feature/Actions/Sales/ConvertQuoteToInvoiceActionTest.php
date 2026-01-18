<?php

namespace Modules\Sales\Tests\Feature\Actions;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Sales\Actions\Sales\ConvertQuoteToInvoiceAction;
use Modules\Sales\Enums\Sales\QuoteStatus;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteLine;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    $this->currency = Currency::factory()->create(['code' => 'USD', 'symbol' => '$']);
    $this->customer = Partner::factory()->customer()->create(['company_id' => $this->company->id]);
});

it('convert quote to invoice success', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'partner_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => QuoteStatus::Accepted,
    ]);

    $incomeAccount = \Modules\Accounting\Models\Account::factory()->create(['company_id' => $this->company->id]);

    QuoteLine::factory()->create([
        'quote_id' => $quote->id,
        'product_id' => \Modules\Product\Models\Product::factory()->create(['company_id' => $this->company->id])->id,
        'quantity' => 1,
        'unit_price' => Money::of(100, 'USD'),
        'income_account_id' => $incomeAccount->id,
        'tax_id' => null,
        'description' => 'Test Item',
    ]);

    $action = app(ConvertQuoteToInvoiceAction::class);
    $invoice = $action->execute($quote);

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
    ]);

    $this->assertDatabaseHas('quotes', [
        'id' => $quote->id,
        'status' => QuoteStatus::Converted,
        'converted_to_invoice_id' => $invoice->id,
    ]);

    expect($invoice->invoiceLines)->toHaveCount(1);
    expect($invoice->invoiceLines->first()->unit_price->getAmount()->toInt())->toBe(100);
});

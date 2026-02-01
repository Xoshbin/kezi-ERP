<?php

namespace Jmeryar\Sales\Tests\Feature\Actions;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\Event;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Sales\Actions\Sales\ConvertQuoteToInvoiceAction;
use Jmeryar\Sales\Enums\Sales\QuoteStatus;
use Jmeryar\Sales\Events\QuoteConverted;
use Jmeryar\Sales\Exceptions\QuoteCannotBeModifiedException;
use Jmeryar\Sales\Models\Quote;
use Jmeryar\Sales\Models\QuoteLine;
use Tests\Traits\WithConfiguredCompany;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
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

    $incomeAccount = \Jmeryar\Accounting\Models\Account::factory()->create(['company_id' => $this->company->id]);

    QuoteLine::factory()->create([
        'quote_id' => $quote->id,
        'product_id' => \Jmeryar\Product\Models\Product::factory()->create(['company_id' => $this->company->id])->id,
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

it('throws exception if quote is not accepted', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Draft, // Not Accepted
    ]);

    $action = app(ConvertQuoteToInvoiceAction::class);

    expect(fn () => $action->execute($quote))
        ->toThrow(QuoteCannotBeModifiedException::class);
});

it('dispatches QuoteConverted event and sets timestamp', function () {
    Event::fake();

    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'partner_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => QuoteStatus::Accepted,
    ]);

    $incomeAccount = \Jmeryar\Accounting\Models\Account::factory()->create(['company_id' => $this->company->id]);

    QuoteLine::factory()->create([
        'quote_id' => $quote->id,
        'product_id' => \Jmeryar\Product\Models\Product::factory()->create(['company_id' => $this->company->id])->id,
        'quantity' => 1,
        'unit_price' => Money::of(100, 'USD'),
        'income_account_id' => $incomeAccount->id,
    ]);

    $action = app(ConvertQuoteToInvoiceAction::class);
    $invoice = $action->execute($quote);

    Event::assertDispatched(QuoteConverted::class, function ($event) use ($quote, $invoice) {
        return $event->quote->id === $quote->id
            && $event->convertedTo === 'invoice'
            && $event->targetDocument->id === $invoice->id;
    });

    $quote->refresh();
    expect($quote->converted_at)->not->toBeNull();
});

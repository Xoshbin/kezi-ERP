<?php

use App\Models\User;
use Brick\Money\Money;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Sales\Actions\Sales\ConvertQuoteToSalesOrderAction;
use Modules\Sales\Enums\Sales\QuoteStatus;
use Modules\Sales\Exceptions\QuoteCannotBeModifiedException;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteLine;
use Modules\Sales\Models\SalesOrder;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->setupWithConfiguredCompany();
    $this->user = User::factory()->create();
    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->partner = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'customer',
    ]);
    /** @var \Modules\Product\Models\Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->product = $product;
});

test('it can convert an accepted quote to a sales order', function () {
    /** @var \Tests\TestCase $this */
    /** @var \Modules\Product\Models\Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'partner_id' => $this->partner->id,
        'currency_id' => $this->currency->id,
        'status' => QuoteStatus::Accepted,
        'created_by_user_id' => $this->user->id,
    ]);

    QuoteLine::factory()->create([
        'quote_id' => $quote->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => Money::of(100, 'USD'),
    ]);

    $action = app(ConvertQuoteToSalesOrderAction::class);
    $salesOrder = $action->execute($quote);

    expect($salesOrder)->toBeInstanceOf(SalesOrder::class)
        ->and($salesOrder->reference)->toBe($quote->quote_number)
        ->and($salesOrder->lines)->toHaveCount(1);

    $line = $salesOrder->lines->first();
    $this->assertNotNull($line);
    expect($line->product_id)->toBe($product->id)
        ->and($line->quantity)->toEqual(10.0);

    // Verify quote status update
    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Converted)
        ->and($quote->converted_to_sales_order_id)->toBe($salesOrder->id);
});

test('it throws exception when converting a non-accepted quote', function () {
    /** @var \Tests\TestCase $this */
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Draft,
        'created_by_user_id' => $this->user->id,
        'partner_id' => $this->partner->id,
        'currency_id' => $this->currency->id,
    ]);

    $action = app(ConvertQuoteToSalesOrderAction::class);

    expect(fn () => $action->execute($quote))
        ->toThrow(QuoteCannotBeModifiedException::class);
});

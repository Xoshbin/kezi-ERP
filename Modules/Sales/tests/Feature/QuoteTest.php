<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Sales\Actions\Sales\AcceptQuoteAction;
use Modules\Sales\Actions\Sales\CancelQuoteAction;
use Modules\Sales\Actions\Sales\ConvertQuoteToSalesOrderAction;
use Modules\Sales\Actions\Sales\CreateQuoteAction;
use Modules\Sales\Actions\Sales\CreateQuoteRevisionAction;
use Modules\Sales\Actions\Sales\RejectQuoteAction;
use Modules\Sales\Actions\Sales\SendQuoteAction;
use Modules\Sales\DataTransferObjects\Sales\CreateQuoteDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateQuoteLineDTO;
use Modules\Sales\Enums\Sales\QuoteStatus;
use Modules\Sales\Exceptions\QuoteCannotBeModifiedException;
use Modules\Sales\Models\Quote;
use Tests\Traits\WithConfiguredCompany;

/**
 * @property \App\Models\Company $company
 * @property \Modules\User\Models\User $user
 */
uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can create a quote with lines', function () {
    $partner = Partner::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    $currency = Currency::where('code', 'IQD')->first();

    $dto = new CreateQuoteDTO(
        companyId: $this->company->id,
        partnerId: $partner->id,
        currencyId: $currency->id,
        quoteDate: now(),
        validUntil: now()->addDays(30),
        lines: [
            new CreateQuoteLineDTO(
                description: 'Test Product',
                quantity: 2,
                unitPrice: Money::of(100, 'IQD'),
                productId: $product->id,
            ),
        ],
        createdByUserId: $this->user->id,
    );

    $quote = app(CreateQuoteAction::class)->execute($dto);

    expect($quote)
        ->toBeInstanceOf(Quote::class)
        ->status->toBe(QuoteStatus::Draft)
        ->quote_number->toStartWith('QT')
        ->and($quote->lines)->toHaveCount(1);
});

it('generates unique quote numbers', function () {
    $partner = Partner::factory()->for($this->company)->create();
    $currency = Currency::where('code', 'IQD')->first();

    $dto1 = new CreateQuoteDTO(
        companyId: $this->company->id,
        partnerId: $partner->id,
        currencyId: $currency->id,
        quoteDate: now(),
        validUntil: now()->addDays(30),
        lines: [],
        createdByUserId: $this->user->id,
    );

    $dto2 = new CreateQuoteDTO(
        companyId: $this->company->id,
        partnerId: $partner->id,
        currencyId: $currency->id,
        quoteDate: now(),
        validUntil: now()->addDays(30),
        lines: [],
        createdByUserId: $this->user->id,
    );

    $quote1 = app(CreateQuoteAction::class)->execute($dto1);
    $quote2 = app(CreateQuoteAction::class)->execute($dto2);

    expect($quote1->quote_number)->not->toBe($quote2->quote_number);
});

it('can send a draft quote', function () {
    $quote = Quote::factory()
        ->for($this->company)
        ->for(Partner::factory()->for($this->company), 'partner')
        ->for(Currency::where('code', 'IQD')->first())
        ->draft()
        ->create();

    // Add a line so it can be sent
    $quote->lines()->create([
        'quote_id' => $quote->id,
        'description' => 'Test',
        'quantity' => 1,
        'unit_price' => 1000,
        'discount_percentage' => 0,
        'discount_amount' => 0,
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total' => 1000,
    ]);

    $result = app(SendQuoteAction::class)->execute($quote);

    expect($result->status)->toBe(QuoteStatus::Sent);
});

it('cannot send a quote without lines', function () {
    $quote = Quote::factory()
        ->for($this->company)
        ->for(Partner::factory()->for($this->company), 'partner')
        ->for(Currency::where('code', 'IQD')->first())
        ->draft()
        ->create();

    expect(fn () => app(SendQuoteAction::class)->execute($quote))
        ->toThrow(QuoteCannotBeModifiedException::class);
});

it('can accept a sent quote', function () {
    $quote = Quote::factory()
        ->for($this->company)
        ->for(Partner::factory()->for($this->company), 'partner')
        ->for(Currency::where('code', 'IQD')->first())
        ->sent()
        ->create();

    $result = app(AcceptQuoteAction::class)->execute($quote);

    expect($result->status)->toBe(QuoteStatus::Accepted);
});

it('can reject a sent quote with reason', function () {
    $quote = Quote::factory()
        ->for($this->company)
        ->for(Partner::factory()->for($this->company), 'partner')
        ->for(Currency::where('code', 'IQD')->first())
        ->sent()
        ->create();

    $result = app(RejectQuoteAction::class)->execute($quote, 'Price too high');

    expect($result)
        ->status->toBe(QuoteStatus::Rejected)
        ->rejection_reason->toBe('Price too high');
});

it('can convert accepted quote to sales order', function () {
    $partner = Partner::factory()->for($this->company)->create();
    $currency = Currency::where('code', 'IQD')->first();
    $product = Product::factory()->for($this->company)->create();

    $quote = Quote::factory()
        ->for($this->company)
        ->for($partner, 'partner')
        ->for($currency)
        ->accepted()
        ->create();

    $quote->lines()->create([
        'quote_id' => $quote->id,
        'product_id' => $product->id,
        'description' => 'Test Product',
        'quantity' => 2,
        'unit_price' => 50000,
        'discount_percentage' => 0,
        'discount_amount' => 0,
        'subtotal' => 100000,
        'tax_amount' => 0,
        'total' => 100000,
    ]);

    $salesOrder = app(ConvertQuoteToSalesOrderAction::class)->execute($quote, $this->user->id);

    expect($salesOrder)
        ->toBeInstanceOf(\Modules\Sales\Models\SalesOrder::class)
        ->customer_id->toBe($partner->id)
        ->currency_id->toBe($currency->id);

    $quote->refresh();
    expect($quote)
        ->status->toBe(QuoteStatus::Converted)
        ->converted_to_sales_order_id->toBe($salesOrder->id);
});

it('can create quote revision', function () {
    $quote = Quote::factory()
        ->for($this->company)
        ->for(Partner::factory()->for($this->company), 'partner')
        ->for(Currency::where('code', 'IQD')->first())
        ->sent()
        ->create(['version' => 1]);

    $quote->lines()->create([
        'quote_id' => $quote->id,
        'description' => 'Original Line',
        'quantity' => 1,
        'unit_price' => 1000,
        'discount_percentage' => 0,
        'discount_amount' => 0,
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total' => 1000,
    ]);

    $newQuote = app(CreateQuoteRevisionAction::class)->execute($quote);

    expect($newQuote)
        ->version->toBe(2)
        ->previous_version_id->toBe($quote->id)
        ->status->toBe(QuoteStatus::Draft);

    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Cancelled);
});

it('prevents modification of converted quotes', function () {
    $quote = Quote::factory()
        ->for($this->company)
        ->for(Partner::factory()->for($this->company), 'partner')
        ->for(Currency::where('code', 'IQD')->first())
        ->converted()
        ->create();

    expect(fn () => $quote->update(['notes' => 'Changed']))
        ->toThrow(QuoteCannotBeModifiedException::class);
});

it('prevents deletion of non-draft quotes', function () {
    $quote = Quote::factory()
        ->for($this->company)
        ->for(Partner::factory()->for($this->company), 'partner')
        ->for(Currency::where('code', 'IQD')->first())
        ->sent()
        ->create();

    expect(fn () => $quote->delete())
        ->toThrow(QuoteCannotBeModifiedException::class);
});

it('can cancel a quote', function () {
    $quote = Quote::factory()
        ->for($this->company)
        ->for(Partner::factory()->for($this->company), 'partner')
        ->for(Currency::where('code', 'IQD')->first())
        ->draft()
        ->create();

    $result = app(CancelQuoteAction::class)->execute($quote);

    expect($result->status)->toBe(QuoteStatus::Cancelled);
});

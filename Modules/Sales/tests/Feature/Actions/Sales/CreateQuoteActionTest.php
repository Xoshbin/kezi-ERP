<?php

use App\Models\User;
use Brick\Money\Money;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Sales\Actions\Sales\CreateQuoteAction;
use Modules\Sales\DataTransferObjects\Sales\CreateQuoteDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateQuoteLineDTO;
use Modules\Sales\Models\Quote;
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

test('it can create a quote with lines', function () {
    /** @var \Tests\TestCase $this */
    $quoteDto = new CreateQuoteDTO(
        companyId: $this->company->id,
        partnerId: $this->partner->id,
        currencyId: $this->currency->id,
        createdByUserId: $this->user->id,
        quoteDate: now(),
        validUntil: now()->addDays(30),
        exchangeRate: 1.0,
        notes: 'Test Quote',
        termsAndConditions: 'Standard Terms',
        lines: [
            new CreateQuoteLineDTO(
                productId: $this->product->id,
                description: 'Test Product',
                quantity: 10,
                unit: 'pcs',
                unitPrice: Money::of(100, 'USD'),
                discountPercentage: 0,
                taxId: null,
                incomeAccountId: null,
            ),
        ],
    );

    $action = app(CreateQuoteAction::class);
    $quote = $action->execute($quoteDto);

    expect($quote)->toBeInstanceOf(Quote::class)
        ->and($quote->company_id)->toBe($this->company->id)
        ->and($quote->partner_id)->toBe($this->partner->id)
        ->and($quote->currency_id)->toBe($this->currency->id)
        ->and($quote->total->getAmount()->toFloat())->toBe(1000.0);

    $line = $quote->lines->first();
    expect($line)->not->toBeNull();
    /** @var \Modules\Sales\Models\QuoteLine $line */
    expect($line->product_id)->toBe($this->product->id)
        ->and($line->unit_price->getAmount()->toFloat())->toBe(100.0);
});

test('it creates a quote without lines', function () {
    $quoteDto = new CreateQuoteDTO(
        companyId: $this->company->id,
        partnerId: $this->partner->id,
        currencyId: $this->currency->id,
        createdByUserId: $this->user->id,
        quoteDate: now(),
        validUntil: now()->addDays(30),
        exchangeRate: 1.0,
        lines: [],
    );

    $action = app(CreateQuoteAction::class);
    $quote = $action->execute($quoteDto);

    expect($quote)->toBeInstanceOf(Quote::class)
        ->and($quote->lines)->toBeEmpty()
        ->and($quote->total->getAmount()->toFloat())->toBe(0.0);
});

test('it generates unique quote numbers', function () {
    $quoteDto1 = new CreateQuoteDTO(
        companyId: $this->company->id,
        partnerId: $this->partner->id,
        currencyId: $this->currency->id,
        createdByUserId: $this->user->id,
        quoteDate: now(),
        validUntil: now()->addDays(30),
        exchangeRate: 1.0,
        lines: [],
    );

    $action = app(CreateQuoteAction::class);
    $quote1 = $action->execute($quoteDto1);
    $quote2 = $action->execute($quoteDto1);

    expect($quote1->quote_number)->not->toBeEmpty();
    expect($quote2->quote_number)->not->toBeEmpty();
    expect($quote1->quote_number)->not->toBe($quote2->quote_number);
});

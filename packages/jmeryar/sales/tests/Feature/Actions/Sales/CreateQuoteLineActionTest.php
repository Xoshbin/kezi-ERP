<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Tax;
use Jmeryar\Product\Models\Product;
use Jmeryar\Sales\Actions\Sales\CreateQuoteLineAction;
use Jmeryar\Sales\DataTransferObjects\Sales\CreateQuoteLineDTO;
use Jmeryar\Sales\Models\Quote;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateQuoteLineAction::class);
});

it('can create a quote line', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $tax = Tax::factory()->create(['company_id' => $this->company->id]);
    $account = Account::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateQuoteLineDTO(
        description: 'Test Product Description',
        quantity: 2.0,
        unitPrice: Money::of(100, $this->company->currency->code),
        productId: $product->id,
        taxId: $tax->id,
        incomeAccountId: $account->id,
        unit: 'pcs',
        discountPercentage: 10.0,
        lineOrder: 1
    );

    $line = $this->action->execute($quote, $dto);

    expect($line->quote_id)->toBe($quote->id)
        ->and($line->product_id)->toBe($product->id)
        ->and($line->description)->toBe('Test Product Description')
        ->and((float) $line->quantity)->toBe(2.0)
        ->and($line->unit_price->getAmount()->toFloat())->toBe(100.0)
        ->and((float) $line->discount_percentage)->toBe(10.0);

    // Verify calculations (handled by observer)
    // Subtotal = 2 * 100 = 200
    // Discount = 200 * 0.1 = 20
    // Subtotal after discount = 180
    // Total depends on tax
    expect($line->subtotal->getAmount()->toFloat())->toBe(180.0);
});

it('calculates subtotal without discount', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $account = Account::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateQuoteLineDTO(
        description: 'Test Product',
        quantity: 5.0,
        unitPrice: Money::of(200, $this->company->currency->code),
        productId: $product->id,
        taxId: null,
        incomeAccountId: $account->id,
        unit: 'pcs',
        discountPercentage: 0.0,
    );

    $line = $this->action->execute($quote, $dto);

    // 5 * 200 = 1000
    expect($line->subtotal->getAmount()->toFloat())->toBe(1000.0);
    expect((float) $line->discount_amount->getAmount()->toFloat())->toBe(0.0);
});

it('calculates tax correctly', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $tax = Tax::factory()->create([
        'company_id' => $this->company->id,
        'rate' => 10.0, // 10%
        'type' => \Jmeryar\Accounting\Enums\Accounting\TaxType::Sales,
    ]);
    $account = Account::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateQuoteLineDTO(
        description: 'Test Product',
        quantity: 1.0,
        unitPrice: Money::of(100, $this->company->currency->code),
        productId: $product->id,
        taxId: $tax->id,
        incomeAccountId: $account->id,
        unit: 'pcs',
        discountPercentage: 0.0,
    );

    $line = $this->action->execute($quote, $dto);

    // Subtotal: 100
    // Tax: 100 * 0.10 = 10
    // Total: 110
    expect($line->subtotal->getAmount()->toFloat())->toBe(100.0);
    expect($line->tax_amount->getAmount()->toFloat())->toBe(10.0);
    expect($line->total->getAmount()->toFloat())->toBe(110.0);
});

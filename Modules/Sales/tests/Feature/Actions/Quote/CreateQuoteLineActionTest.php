<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Tax;
use Modules\Product\Models\Product;
use Modules\Sales\Actions\Sales\CreateQuoteLineAction;
use Modules\Sales\DataTransferObjects\Sales\CreateQuoteLineDTO;
use Modules\Sales\Models\Quote;
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

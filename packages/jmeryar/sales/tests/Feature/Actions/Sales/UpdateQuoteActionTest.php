<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Tax;
use Jmeryar\Product\Models\Product;
use Jmeryar\Sales\Actions\Sales\UpdateQuoteAction;
use Jmeryar\Sales\DataTransferObjects\Sales\UpdateQuoteDTO;
use Jmeryar\Sales\DataTransferObjects\Sales\UpdateQuoteLineDTO;
use Jmeryar\Sales\Exceptions\QuoteCannotBeModifiedException;
use Jmeryar\Sales\Models\Quote;
use Jmeryar\Sales\Models\QuoteLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(UpdateQuoteAction::class);
});

it('can update quote header details', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'notes' => 'Old Notes',
    ]);

    $dto = new UpdateQuoteDTO(
        quoteId: $quote->id,
        notes: 'Updated Notes',
        validUntil: now()->addMonth()
    );

    $result = $this->action->execute($dto);

    expect($result->notes)->toBe('Updated Notes')
        ->and($result->valid_until->toDateString())->toBe(now()->addMonth()->toDateString());
});

it('can update existing lines, add new lines, and delete lines', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $existingLine = QuoteLine::factory()->create([
        'quote_id' => $quote->id,
        'quantity' => 1,
        'unit_price' => Money::of(100, $this->company->currency->code),
    ]);

    $lineToDelete = QuoteLine::factory()->create([
        'quote_id' => $quote->id,
    ]);

    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $tax = Tax::factory()->create(['company_id' => $this->company->id]);
    $account = Account::factory()->create(['company_id' => $this->company->id]);

    $lines = [
        // Update existing line
        new UpdateQuoteLineDTO(
            lineId: $existingLine->id,
            quantity: 5.0,
            unitPrice: Money::of(120, $this->company->currency->code)
        ),
        // Add new line
        new UpdateQuoteLineDTO(
            description: 'New Line',
            quantity: 2.0,
            unitPrice: Money::of(50, $this->company->currency->code),
            productId: $product->id,
            taxId: $tax->id,
            incomeAccountId: $account->id,
            unit: 'pcs'
        ),
        // Delete line
        new UpdateQuoteLineDTO(
            lineId: $lineToDelete->id,
            shouldDelete: true
        ),
    ];

    $dto = new UpdateQuoteDTO(
        quoteId: $quote->id,
        lines: $lines
    );

    $result = $this->action->execute($dto);

    $quote->refresh();

    expect($quote->lines)->toHaveCount(2);

    $updatedLine = $quote->lines->firstWhere('id', $existingLine->id);
    expect((float) $updatedLine->quantity)->toBe(5.0)
        ->and($updatedLine->unit_price->getAmount()->toFloat())->toBe(120.0);

    expect($quote->lines->where('description', 'New Line')->count())->toBe(1);
    expect(QuoteLine::find($lineToDelete->id))->toBeNull();

    // Check totals recalculation
    // Line 1: 5 * 120 = 600
    // Line 2: 2 * 50 = 100
    // Total subtotal = 700 (assuming no discounts/taxes in factory for these specific lines or handled by observer)
    expect($quote->subtotal->getAmount()->toFloat())->toBe(700.0);
});

it('throws exception when updating a locked quote', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => \Jmeryar\Sales\Enums\Sales\QuoteStatus::Converted,
    ]);

    $dto = new UpdateQuoteDTO(quoteId: $quote->id, notes: 'Try to update');

    expect(fn () => $this->action->execute($dto))
        ->toThrow(QuoteCannotBeModifiedException::class);
});

<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Sales\Actions\Sales\CreateQuoteRevisionAction;
use Kezi\Sales\Enums\Sales\QuoteStatus;
use Kezi\Sales\Exceptions\QuoteCannotBeModifiedException;
use Kezi\Sales\Models\Quote;
use Kezi\Sales\Models\QuoteLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateQuoteRevisionAction::class);
});

it('can create a revision from a sent quote', function () {
    $originalQuote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Sent,
        'version' => 1,
        'previous_version_id' => null,
    ]);

    $line = QuoteLine::factory()->create([
        'quote_id' => $originalQuote->id,
        'quantity' => 10,
        'unit_price' => 100,
    ]);

    $newQuote = $this->action->execute($originalQuote);

    // Verify new quote
    expect($newQuote)->toBeInstanceOf(Quote::class)
        ->and($newQuote->id)->not->toBe($originalQuote->id)
        ->and($newQuote->version)->toBe(2)
        ->and($newQuote->previous_version_id)->toBe($originalQuote->id)
        ->and($newQuote->status)->toBe(QuoteStatus::Draft)
        ->and($newQuote->lines)->toHaveCount(1);

    // Verify line copied
    $newLine = $newQuote->lines->first();
    expect($newLine->product_id)->toBe($line->product_id)
        ->and((float) $newLine->quantity)->toBe(10.0)
        ->and($newLine->unit_price->getAmount()->toFloat())->toBe(100.0);

    // Verify original quote cancelled
    expect($originalQuote->refresh()->status)->toBe(QuoteStatus::Cancelled);
});

it('can create a revision from a rejected quote', function () {
    $originalQuote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Rejected,
        'version' => 1,
    ]);

    $newQuote = $this->action->execute($originalQuote);

    expect($newQuote->version)->toBe(2)
        ->and($originalQuote->refresh()->status)->toBe(QuoteStatus::Cancelled);
});

it('cannot create a revision from a draft quote', function () {
    $originalQuote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Draft,
    ]);

    expect(fn () => $this->action->execute($originalQuote))
        ->toThrow(QuoteCannotBeModifiedException::class);
});

it('cannot create a revision from a converted quote', function () {
    $originalQuote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Converted,
    ]);

    expect(fn () => $this->action->execute($originalQuote))
        ->toThrow(QuoteCannotBeModifiedException::class);
});

it('cannot create a revision from a cancelled quote', function () {
    $originalQuote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Cancelled,
    ]);

    expect(fn () => $this->action->execute($originalQuote))
        ->toThrow(QuoteCannotBeModifiedException::class);
});

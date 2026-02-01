<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Jmeryar\Sales\Actions\Sales\SendQuoteAction;
use Jmeryar\Sales\Enums\Sales\QuoteStatus;
use Jmeryar\Sales\Events\QuoteSent;
use Jmeryar\Sales\Exceptions\QuoteCannotBeModifiedException;
use Jmeryar\Sales\Models\Quote;
use Jmeryar\Sales\Models\QuoteLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(SendQuoteAction::class);
});

it('marks a draft quote as sent if it has lines', function () {
    Event::fake();

    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Draft,
    ]);

    QuoteLine::factory()->create([
        'quote_id' => $quote->id,
    ]);

    $result = $this->action->execute($quote);

    expect($result->status)->toBe(QuoteStatus::Sent);
    Event::assertDispatched(QuoteSent::class, fn ($event) => $event->quote->id === $quote->id);
});

it('throws exception if quote is not draft', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Sent,
    ]);

    expect(fn () => $this->action->execute($quote))
        ->toThrow(QuoteCannotBeModifiedException::class, 'Only draft quotes can be sent.');
});

it('throws exception if quote has no lines', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Draft,
    ]);

    expect(fn () => $this->action->execute($quote))
        ->toThrow(QuoteCannotBeModifiedException::class, 'Cannot send a quote without line items.');
});

<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Sales\Actions\Sales\SendQuoteAction;
use Modules\Sales\Enums\Sales\QuoteStatus;
use Modules\Sales\Events\QuoteSent;
use Modules\Sales\Exceptions\QuoteCannotBeModifiedException;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteLine;
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

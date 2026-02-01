<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Jmeryar\Sales\Actions\Sales\AcceptQuoteAction;
use Jmeryar\Sales\Enums\Sales\QuoteStatus;
use Jmeryar\Sales\Events\QuoteAccepted;
use Jmeryar\Sales\Exceptions\QuoteCannotBeModifiedException;
use Jmeryar\Sales\Models\Quote;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(AcceptQuoteAction::class);
});

it('marks a sent quote as accepted', function () {
    Event::fake();

    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Sent,
        'valid_until' => now()->addDays(7),
    ]);

    $result = $this->action->execute($quote);

    expect($result->status)->toBe(QuoteStatus::Accepted);
    Event::assertDispatched(QuoteAccepted::class, fn ($event) => $event->quote->id === $quote->id);
});

it('throws exception if quote is not in sent status', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Draft,
    ]);

    expect(fn () => $this->action->execute($quote))
        ->toThrow(QuoteCannotBeModifiedException::class, 'Only sent quotes can be accepted.');
});

it('throws exception if quote is expired', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Sent,
        'valid_until' => now()->subDay(),
    ]);

    expect(fn () => $this->action->execute($quote))
        ->toThrow(QuoteCannotBeModifiedException::class, 'Cannot accept an expired quote. Please create a new revision.');
});

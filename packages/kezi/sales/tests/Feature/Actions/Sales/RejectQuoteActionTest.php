<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Kezi\Sales\Actions\Sales\RejectQuoteAction;
use Kezi\Sales\Enums\Sales\QuoteStatus;
use Kezi\Sales\Events\QuoteRejected;
use Kezi\Sales\Exceptions\QuoteCannotBeModifiedException;
use Kezi\Sales\Models\Quote;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(RejectQuoteAction::class);
});

it('marks a sent quote as rejected with a reason', function () {
    Event::fake();

    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Sent,
    ]);

    $reason = 'Too expensive';
    $result = $this->action->execute($quote, $reason);

    expect($result->status)->toBe(QuoteStatus::Rejected);
    expect($result->rejection_reason)->toBe($reason);
    Event::assertDispatched(QuoteRejected::class, fn ($event) => $event->quote->id === $quote->id);
});

it('throws exception if quote is not in sent status', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Draft,
    ]);

    expect(fn () => $this->action->execute($quote))
        ->toThrow(QuoteCannotBeModifiedException::class);
});

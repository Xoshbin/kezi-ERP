<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Sales\Actions\Sales\CancelQuoteAction;
use Jmeryar\Sales\Enums\Sales\QuoteStatus;
use Jmeryar\Sales\Exceptions\QuoteCannotBeModifiedException;
use Jmeryar\Sales\Models\Quote;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CancelQuoteAction::class);
});

it('can cancel a draft quote', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Draft,
    ]);

    $result = $this->action->execute($quote);

    expect($result->status)->toBe(QuoteStatus::Cancelled);
});

it('can cancel a sent quote', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Sent,
    ]);

    $result = $this->action->execute($quote);

    expect($result->status)->toBe(QuoteStatus::Cancelled);
});

it('can cancel an accepted quote', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Accepted,
    ]);

    $result = $this->action->execute($quote);

    expect($result->status)->toBe(QuoteStatus::Cancelled);
});

it('cannot cancel a converted quote', function () {
    $quote = Quote::factory()->create([
        'company_id' => $this->company->id,
        'status' => QuoteStatus::Converted,
    ]);

    expect(fn () => $this->action->execute($quote))
        ->toThrow(QuoteCannotBeModifiedException::class);
});

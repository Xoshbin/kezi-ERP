<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Jmeryar\Purchase\Actions\Purchases\SendRequestForQuotationAction;
use Jmeryar\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Jmeryar\Purchase\Events\RequestForQuotationSent;
use Jmeryar\Purchase\Models\RequestForQuotation;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(SendRequestForQuotationAction::class);
});

it('updates RFQ status to sent and dispatches event', function () {
    Event::fake([RequestForQuotationSent::class]);

    $rfq = RequestForQuotation::factory()->create([
        'company_id' => $this->company->id,
        'status' => RequestForQuotationStatus::Draft,
    ]);

    $updatedRfq = $this->action->execute($rfq);

    expect($updatedRfq->status)->toBe(RequestForQuotationStatus::Sent);

    Event::assertDispatched(RequestForQuotationSent::class, function ($event) use ($rfq) {
        return $event->rfq->id === $rfq->id;
    });
});

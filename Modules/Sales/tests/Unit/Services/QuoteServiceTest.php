<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Sales\Actions\Sales\CreateQuoteAction;
use Modules\Sales\DataTransferObjects\Sales\CreateQuoteDTO;
use Modules\Sales\Enums\Sales\QuoteStatus;
use Modules\Sales\Events\QuoteCreated;
use Modules\Sales\Events\QuoteExpired;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteLine;
use Modules\Sales\Services\QuoteService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->createAction = Mockery::mock(CreateQuoteAction::class);

    $this->service = new QuoteService(
        createAction: $this->createAction,
        updateAction: Mockery::mock(\Modules\Sales\Actions\Sales\UpdateQuoteAction::class),
        sendAction: Mockery::mock(\Modules\Sales\Actions\Sales\SendQuoteAction::class),
        acceptAction: Mockery::mock(\Modules\Sales\Actions\Sales\AcceptQuoteAction::class),
        rejectAction: Mockery::mock(\Modules\Sales\Actions\Sales\RejectQuoteAction::class),
        cancelAction: Mockery::mock(\Modules\Sales\Actions\Sales\CancelQuoteAction::class),
        convertToOrderAction: Mockery::mock(\Modules\Sales\Actions\Sales\ConvertQuoteToSalesOrderAction::class),
        convertToInvoiceAction: Mockery::mock(\Modules\Sales\Actions\Sales\ConvertQuoteToInvoiceAction::class),
        revisionAction: Mockery::mock(\Modules\Sales\Actions\Sales\CreateQuoteRevisionAction::class),
    );
});

it('duplicates a quote with copied data', function () {
    Event::fake([QuoteCreated::class]);

    $originalQuote = Quote::factory()
        ->has(QuoteLine::factory()->count(2), 'lines')
        ->create([
            'status' => QuoteStatus::Accepted,
            'notes' => 'Original Notes',
        ]);

    $this->createAction->shouldReceive('execute')
        ->once()
        ->with(Mockery::on(function (CreateQuoteDTO $dto) use ($originalQuote) {
            return $dto->partnerId === $originalQuote->partner_id &&
                   $dto->notes === $originalQuote->notes &&
                   count($dto->lines) === 2 &&
                   $dto->quoteDate->isToday();
        }))
        ->andReturn(Quote::factory()->make());

    $newQuote = $this->service->duplicate($originalQuote);

    expect($newQuote)->toBeInstanceOf(Quote::class);
    Event::assertDispatched(QuoteCreated::class);
});

it('marks expired quotes and dispatches events', function () {
    Event::fake([QuoteExpired::class]);

    // Create an expired quote
    $expiredQuote = Quote::factory()->create([
        'valid_until' => now()->subDay(),
        'status' => QuoteStatus::Sent,
    ]);

    // Create a non-expired quote
    Quote::factory()->create([
        'valid_until' => now()->addDay(),
        'status' => QuoteStatus::Sent,
    ]);

    // Create an already accepted quote that passed validity but shouldn't expire
    Quote::factory()->create([
        'valid_until' => now()->subDay(),
        'status' => QuoteStatus::Accepted,
    ]);

    $count = $this->service->checkExpiredQuotes();

    expect($count)->toBe(1);
    expect($expiredQuote->refresh()->status)->toBe(QuoteStatus::Expired);
    Event::assertDispatched(QuoteExpired::class, function (QuoteExpired $event) use ($expiredQuote) {
        return $event->quote->id === $expiredQuote->id;
    });
});

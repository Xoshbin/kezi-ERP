<?php

use Illuminate\Support\Facades\Event;
use Kezi\Sales\Actions\Sales\AcceptQuoteAction;
use Kezi\Sales\Actions\Sales\CancelQuoteAction;
use Kezi\Sales\Actions\Sales\ConvertQuoteToInvoiceAction;
use Kezi\Sales\Actions\Sales\ConvertQuoteToSalesOrderAction;
use Kezi\Sales\Actions\Sales\CreateQuoteAction;
use Kezi\Sales\Actions\Sales\CreateQuoteRevisionAction;
use Kezi\Sales\Actions\Sales\RejectQuoteAction;
use Kezi\Sales\Actions\Sales\SendQuoteAction;
use Kezi\Sales\Actions\Sales\UpdateQuoteAction;
use Kezi\Sales\DataTransferObjects\Sales\CreateQuoteDTO;
use Kezi\Sales\DataTransferObjects\Sales\UpdateQuoteDTO;
use Kezi\Sales\Enums\Sales\QuoteStatus;
use Kezi\Sales\Events\QuoteCreated;
use Kezi\Sales\Events\QuoteExpired;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\Quote;
use Kezi\Sales\Models\QuoteLine;
use Kezi\Sales\Models\SalesOrder;
use Kezi\Sales\Services\QuoteService;

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->createAction = Mockery::mock(CreateQuoteAction::class);
    $this->updateAction = Mockery::mock(UpdateQuoteAction::class);
    $this->sendAction = Mockery::mock(SendQuoteAction::class);
    $this->acceptAction = Mockery::mock(AcceptQuoteAction::class);
    $this->rejectAction = Mockery::mock(RejectQuoteAction::class);
    $this->cancelAction = Mockery::mock(CancelQuoteAction::class);
    $this->convertToOrderAction = Mockery::mock(ConvertQuoteToSalesOrderAction::class);
    $this->convertToInvoiceAction = Mockery::mock(ConvertQuoteToInvoiceAction::class);
    $this->revisionAction = Mockery::mock(CreateQuoteRevisionAction::class);

    $this->service = new QuoteService(
        createAction: $this->createAction,
        updateAction: $this->updateAction,
        sendAction: $this->sendAction,
        acceptAction: $this->acceptAction,
        rejectAction: $this->rejectAction,
        cancelAction: $this->cancelAction,
        convertToOrderAction: $this->convertToOrderAction,
        convertToInvoiceAction: $this->convertToInvoiceAction,
        revisionAction: $this->revisionAction,
    );
});

it('duplicates a quote with copied data', function () {
    Event::fake([QuoteCreated::class]);

    $originalQuote = Quote::factory()
        ->has(QuoteLine::factory()->count(2), 'lines')
        ->create([
            'notes' => 'Some notes',
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

describe('create', function () {
    it('creates a quote and dispatches QuoteCreated event', function () {
        Event::fake([QuoteCreated::class]);

        $dto = new CreateQuoteDTO(
            companyId: 1,
            partnerId: 1,
            currencyId: 1,
            quoteDate: now(),
            validUntil: now()->addDays(30),
            lines: [],
        );

        $expectedQuote = Quote::factory()->make();

        $this->createAction->shouldReceive('execute')
            ->once()
            ->with($dto)
            ->andReturn($expectedQuote);

        $result = $this->service->create($dto);

        expect($result)->toBe($expectedQuote);
        Event::assertDispatched(QuoteCreated::class);
    });
});

describe('update', function () {
    it('delegates to UpdateQuoteAction', function () {
        $dto = new UpdateQuoteDTO(
            quoteId: 1,
            partnerId: 1,
            currencyId: 1,
            quoteDate: now(),
            validUntil: now()->addDays(30),
            lines: [],
        );

        $expectedQuote = Quote::factory()->make();

        $this->updateAction->shouldReceive('execute')
            ->once()
            ->with($dto)
            ->andReturn($expectedQuote);

        $result = $this->service->update($dto);

        expect($result)->toBe($expectedQuote);
    });
});

describe('send', function () {
    it('delegates to SendQuoteAction', function () {
        $quote = Quote::factory()->make();
        $expectedQuote = Quote::factory()->make();

        $this->sendAction->shouldReceive('execute')
            ->once()
            ->with($quote)
            ->andReturn($expectedQuote);

        $result = $this->service->send($quote);

        expect($result)->toBe($expectedQuote);
    });
});

describe('accept', function () {
    it('delegates to AcceptQuoteAction', function () {
        $quote = Quote::factory()->make();
        $expectedQuote = Quote::factory()->make();

        $this->acceptAction->shouldReceive('execute')
            ->once()
            ->with($quote)
            ->andReturn($expectedQuote);

        $result = $this->service->accept($quote);

        expect($result)->toBe($expectedQuote);
    });
});

describe('reject', function () {
    it('delegates to RejectQuoteAction with optional reason', function () {
        $quote = Quote::factory()->make();
        $expectedQuote = Quote::factory()->make();
        $reason = 'Too expensive';

        $this->rejectAction->shouldReceive('execute')
            ->once()
            ->with($quote, $reason)
            ->andReturn($expectedQuote);

        $result = $this->service->reject($quote, $reason);

        expect($result)->toBe($expectedQuote);
    });
});

describe('cancel', function () {
    it('delegates to CancelQuoteAction', function () {
        $quote = Quote::factory()->make();
        $expectedQuote = Quote::factory()->make();

        $this->cancelAction->shouldReceive('execute')
            ->once()
            ->with($quote)
            ->andReturn($expectedQuote);

        $result = $this->service->cancel($quote);

        expect($result)->toBe($expectedQuote);
    });
});

describe('convertToSalesOrder', function () {
    it('delegates to ConvertQuoteToSalesOrderAction with optional userId', function () {
        $quote = Quote::factory()->make();
        $expectedOrder = SalesOrder::factory()->make();
        $userId = 1;

        $this->convertToOrderAction->shouldReceive('execute')
            ->once()
            ->with($quote, $userId)
            ->andReturn($expectedOrder);

        $result = $this->service->convertToSalesOrder($quote, $userId);

        expect($result)->toBe($expectedOrder);
    });
});

describe('convertToInvoice', function () {
    it('delegates to ConvertQuoteToInvoiceAction', function () {
        $quote = Quote::factory()->make();
        $expectedInvoice = Invoice::factory()->make();

        $this->convertToInvoiceAction->shouldReceive('execute')
            ->once()
            ->with($quote)
            ->andReturn($expectedInvoice);

        $result = $this->service->convertToInvoice($quote);

        expect($result)->toBe($expectedInvoice);
    });
});

describe('createRevision', function () {
    it('delegates to CreateQuoteRevisionAction', function () {
        $quote = Quote::factory()->make();
        $expectedRevision = Quote::factory()->make();

        $this->revisionAction->shouldReceive('execute')
            ->once()
            ->with($quote)
            ->andReturn($expectedRevision);

        $result = $this->service->createRevision($quote);

        expect($result)->toBe($expectedRevision);
    });
});

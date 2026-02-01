<?php

namespace Jmeryar\Purchase\Tests\Unit\Services;

use Mockery;
use Jmeryar\Purchase\Actions\Purchases\CancelRequestForQuotationAction;
use Jmeryar\Purchase\Actions\Purchases\ConvertRFQToPurchaseOrderAction;
use Jmeryar\Purchase\Actions\Purchases\CreateRequestForQuotationAction;
use Jmeryar\Purchase\Actions\Purchases\RecordVendorBidAction;
use Jmeryar\Purchase\Actions\Purchases\SendRequestForQuotationAction;
use Jmeryar\Purchase\DataTransferObjects\Purchases\ConvertRFQToPurchaseOrderDTO;
use Jmeryar\Purchase\DataTransferObjects\Purchases\CreateRFQDTO;
use Jmeryar\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO;
use Jmeryar\Purchase\Models\PurchaseOrder;
use Jmeryar\Purchase\Models\RequestForQuotation;
use Jmeryar\Purchase\Services\RequestForQuotationService;

beforeEach(function () {
    $this->createAction = Mockery::mock(CreateRequestForQuotationAction::class);
    $this->sendAction = Mockery::mock(SendRequestForQuotationAction::class);
    $this->recordBidAction = Mockery::mock(RecordVendorBidAction::class);
    $this->convertAction = Mockery::mock(ConvertRFQToPurchaseOrderAction::class);
    $this->cancelAction = Mockery::mock(CancelRequestForQuotationAction::class);

    $this->service = new RequestForQuotationService(
        $this->createAction,
        $this->sendAction,
        $this->recordBidAction,
        $this->convertAction,
        $this->cancelAction
    );
});

describe('createRFQ', function () {
    it('delegates to CreateRequestForQuotationAction', function () {
        $dto = new CreateRFQDTO(
            companyId: 1,
            vendorId: 1,
            currencyId: 1,
            rfqDate: now()
        );
        $expectedRfq = Mockery::mock(RequestForQuotation::class);

        $this->createAction->shouldReceive('execute')
            ->once()
            ->with($dto)
            ->andReturn($expectedRfq);

        $result = $this->service->createRFQ($dto);

        expect($result)->toBe($expectedRfq);
    });
});

describe('sendRFQ', function () {
    it('delegates to SendRequestForQuotationAction', function () {
        $rfq = Mockery::mock(RequestForQuotation::class);
        $expectedRfq = Mockery::mock(RequestForQuotation::class);

        $this->sendAction->shouldReceive('execute')
            ->once()
            ->with($rfq)
            ->andReturn($expectedRfq);

        $result = $this->service->sendRFQ($rfq);

        expect($result)->toBe($expectedRfq);
    });
});

describe('recordBid', function () {
    it('delegates to RecordVendorBidAction', function () {
        $rfq = Mockery::mock(RequestForQuotation::class);
        $dto = new UpdateRFQDTO(
            rfqId: 1
        );
        $expectedRfq = Mockery::mock(RequestForQuotation::class);

        $this->recordBidAction->shouldReceive('execute')
            ->once()
            ->with($rfq, $dto)
            ->andReturn($expectedRfq);

        $result = $this->service->recordBid($rfq, $dto);

        expect($result)->toBe($expectedRfq);
    });
});

describe('convertToPurchaseOrder', function () {
    it('delegates to ConvertRFQToPurchaseOrderAction', function () {
        $dto = new ConvertRFQToPurchaseOrderDTO(
            rfqId: 1,
            poDate: now()
        );
        $expectedPo = Mockery::mock(PurchaseOrder::class);

        $this->convertAction->shouldReceive('execute')
            ->once()
            ->with($dto)
            ->andReturn($expectedPo);

        $result = $this->service->convertToPurchaseOrder($dto);

        expect($result)->toBe($expectedPo);
    });
});

describe('cancelRFQ', function () {
    it('delegates to CancelRequestForQuotationAction', function () {
        $rfq = Mockery::mock(RequestForQuotation::class);
        $expectedRfq = Mockery::mock(RequestForQuotation::class);

        $this->cancelAction->shouldReceive('execute')
            ->once()
            ->with($rfq)
            ->andReturn($expectedRfq);

        $result = $this->service->cancelRFQ($rfq);

        expect($result)->toBe($expectedRfq);
    });
});

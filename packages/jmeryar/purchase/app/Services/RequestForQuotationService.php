<?php

namespace Jmeryar\Purchase\Services;

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

class RequestForQuotationService
{
    public function __construct(
        protected CreateRequestForQuotationAction $createAction,
        protected SendRequestForQuotationAction $sendAction,
        protected RecordVendorBidAction $recordBidAction,
        protected ConvertRFQToPurchaseOrderAction $convertAction,
        protected CancelRequestForQuotationAction $cancelAction,
    ) {}

    public function createRFQ(CreateRFQDTO $dto): RequestForQuotation
    {
        return $this->createAction->execute($dto);
    }

    public function sendRFQ(RequestForQuotation $rfq): RequestForQuotation
    {
        return $this->sendAction->execute($rfq);
    }

    public function recordBid(RequestForQuotation $rfq, UpdateRFQDTO $dto): RequestForQuotation
    {
        return $this->recordBidAction->execute($rfq, $dto);
    }

    public function convertToPurchaseOrder(ConvertRFQToPurchaseOrderDTO $dto): PurchaseOrder
    {
        return $this->convertAction->execute($dto);
    }

    public function cancelRFQ(RequestForQuotation $rfq): RequestForQuotation
    {
        return $this->cancelAction->execute($rfq);
    }
}

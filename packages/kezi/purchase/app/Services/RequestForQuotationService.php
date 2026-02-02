<?php

namespace Kezi\Purchase\Services;

use Kezi\Purchase\Actions\Purchases\CancelRequestForQuotationAction;
use Kezi\Purchase\Actions\Purchases\ConvertRFQToPurchaseOrderAction;
use Kezi\Purchase\Actions\Purchases\CreateRequestForQuotationAction;
use Kezi\Purchase\Actions\Purchases\RecordVendorBidAction;
use Kezi\Purchase\Actions\Purchases\SendRequestForQuotationAction;
use Kezi\Purchase\DataTransferObjects\Purchases\ConvertRFQToPurchaseOrderDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateRFQDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\RequestForQuotation;

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

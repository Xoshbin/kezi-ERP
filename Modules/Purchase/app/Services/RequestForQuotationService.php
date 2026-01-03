<?php

namespace Modules\Purchase\Services;

use Modules\Purchase\Actions\Purchases\CancelRequestForQuotationAction;
use Modules\Purchase\Actions\Purchases\ConvertRFQToPurchaseOrderAction;
use Modules\Purchase\Actions\Purchases\CreateRequestForQuotationAction;
use Modules\Purchase\Actions\Purchases\RecordVendorBidAction;
use Modules\Purchase\Actions\Purchases\SendRequestForQuotationAction;
use Modules\Purchase\DataTransferObjects\Purchases\ConvertRFQToPurchaseOrderDTO;
use Modules\Purchase\DataTransferObjects\Purchases\CreateRFQDTO;
use Modules\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\RequestForQuotation;

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

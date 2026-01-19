<?php

namespace Modules\Sales\Actions\Invoice;

use Exception;
use Illuminate\Support\Str;
use Modules\Inventory\Actions\Adjustments\CreateAdjustmentDocumentAction;
use Modules\Inventory\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO;
use Modules\Inventory\DataTransferObjects\Adjustments\CreateAdjustmentDocumentLineDTO;
use Modules\Inventory\Enums\Adjustments\AdjustmentDocumentType;
use Modules\Inventory\Models\AdjustmentDocument;
use Modules\Sales\DataTransferObjects\Invoice\CreateCreditNoteDTO;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;

class CreateCreditNoteAction
{
    public function __construct(
        private readonly CreateAdjustmentDocumentAction $createAdjustmentDocumentAction,
    ) {}

    public function execute(CreateCreditNoteDTO $dto): AdjustmentDocument
    {
        $invoice = Invoice::findOrFail($dto->invoice_id);

        if ($invoice->status !== InvoiceStatus::Posted && $invoice->status !== InvoiceStatus::Paid) {
            throw new Exception('Credit notes can only be created for confirmed/posted invoices.');
        }

        if ($invoice->company_id !== $dto->company_id) {
            throw new Exception('Invoice does not belong to the requested company.');
        }

        // Map Lines
        $adjustmentLines = array_map(function ($lineDto) {
            return new CreateAdjustmentDocumentLineDTO(
                description: $lineDto->description,
                quantity: $lineDto->quantity,
                unit_price: $lineDto->unit_price,
                account_id: $lineDto->account_id,
                product_id: $lineDto->product_id,
                tax_id: $lineDto->tax_id,
            );
        }, $dto->lines);

        $adjustmentDto = new CreateAdjustmentDocumentDTO(
            company_id: $dto->company_id,
            type: AdjustmentDocumentType::CreditNote,
            date: $dto->date,
            reference_number: $dto->reference_number ?? ('CN-'.$invoice->invoice_number.'-'.Str::random(4)),
            reason: $dto->reason,
            currency_id: $invoice->currency_id,
            original_invoice_id: $invoice->id,
            original_vendor_bill_id: null,
            lines: $adjustmentLines,
        );

        return $this->createAdjustmentDocumentAction->execute($adjustmentDto);
    }
}

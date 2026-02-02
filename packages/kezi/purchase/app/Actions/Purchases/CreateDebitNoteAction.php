<?php

namespace Kezi\Purchase\Actions\Purchases;

use Exception;
use Illuminate\Support\Str;
use Kezi\Inventory\Actions\Adjustments\CreateAdjustmentDocumentAction;
use Kezi\Inventory\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO;
use Kezi\Inventory\DataTransferObjects\Adjustments\CreateAdjustmentDocumentLineDTO;
use Kezi\Inventory\Enums\Adjustments\AdjustmentDocumentType;
use Kezi\Inventory\Models\AdjustmentDocument;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateDebitNoteDTO;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;

class CreateDebitNoteAction
{
    public function __construct(
        private readonly CreateAdjustmentDocumentAction $createAdjustmentDocumentAction,
    ) {}

    public function execute(CreateDebitNoteDTO $dto): AdjustmentDocument
    {
        $vendorBill = VendorBill::findOrFail($dto->vendor_bill_id);

        if ($vendorBill->status !== VendorBillStatus::Posted && $vendorBill->status !== VendorBillStatus::Paid) {
            throw new Exception('Debit notes can only be created for posted/paid vendor bills.');
        }

        if ($vendorBill->company_id !== $dto->company_id) {
            throw new Exception('Vendor bill does not belong to the requested company.');
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
            type: AdjustmentDocumentType::DebitNote,
            date: $dto->date,
            reference_number: $dto->reference_number ?? ('DN-'.$vendorBill->bill_reference.'-'.Str::random(4)),
            reason: $dto->reason,
            currency_id: $vendorBill->currency_id,
            original_invoice_id: null,
            original_vendor_bill_id: $vendorBill->id, // Important linkage
            lines: $adjustmentLines,
        );

        return $this->createAdjustmentDocumentAction->execute($adjustmentDto);
    }
}

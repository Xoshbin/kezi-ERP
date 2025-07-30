<?php

namespace App\Filament\Resources\AdjustmentDocumentResource\Pages;

use App\Actions\Adjustments\CreateAdjustmentDocumentAction;
use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO;
use App\Filament\Resources\AdjustmentDocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAdjustmentDocument extends CreateRecord
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $dto = new CreateAdjustmentDocumentDTO(
            company_id: $data['company_id'],
            type: $data['type'],
            date: $data['date'],
            reference_number: $data['reference_number'],
            total_amount: $data['total_amount'],
            total_tax: $data['total_tax'],
            reason: $data['reason'],
            currency_id: $data['currency_id'],
            original_invoice_id: $data['original_invoice_id'] ?? null,
            original_vendor_bill_id: $data['original_vendor_bill_id'] ?? null,
        );

        return (new CreateAdjustmentDocumentAction())->execute($dto);
    }
}

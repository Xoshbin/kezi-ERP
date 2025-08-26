<?php
// in app/Filament/Resources/AdjustmentDocumentResource/Pages/CreateAdjustmentDocument.php

namespace App\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages;

// Add imports for Invoice and VendorBill
use App\Actions\Adjustments\CreateAdjustmentDocumentAction;
use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO;
use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentLineDTO;
use App\Enums\Adjustments\AdjustmentDocumentType;
use App\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\AdjustmentDocumentResource;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\VendorBill;
use Brick\Money\Money;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

// Other use statements...

class CreateAdjustmentDocument extends CreateRecord
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // --- START OF THE FIX ---
        // 1. Forcefully derive currency_id if it's missing but a source document is linked.
        if (empty($data['currency_id'])) {
            if (!empty($data['original_invoice_id'])) {
                $data['currency_id'] = Invoice::find($data['original_invoice_id'])?->currency_id;
            } elseif (!empty($data['original_vendor_bill_id'])) {
                $data['currency_id'] = VendorBill::find($data['original_vendor_bill_id'])?->currency_id;
            }
        }

        // 2. If currency_id is *still* not found, stop with a clean validation error.
        if (empty($data['currency_id'])) {
            throw ValidationException::withMessages([
                'data.currency_id' => __('validation.required', ['attribute' => 'currency']),
            ]);
        }
        // --- END OF THE FIX ---

        // 3. The rest of the logic can now safely assume currency_id exists.
        $parentCurrencyId = $data['currency_id'];
        $data['lines'] = $data['lines'] ?? [];

        $mutatedLines = [];
        foreach ($data['lines'] as $line) {
            $line['currency_id'] = $parentCurrencyId;
            $mutatedLines[] = $line;
        }
        $data['lines'] = $mutatedLines;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // This method will now always receive a valid $data['currency_id']
        $currency = Currency::find($data['currency_id']);
        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new CreateAdjustmentDocumentLineDTO(
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: Money::of($line['unit_price'], $currency->code),
                account_id: $line['account_id'],
                product_id: $line['product_id'] ?? null,
                tax_id: $line['tax_id'] ?? null
            );
        }

        $dto = new CreateAdjustmentDocumentDTO(
            company_id: \Filament\Facades\Filament::getTenant()->id,
            type: AdjustmentDocumentType::from($data['type']),
            date: $data['date'],
            reference_number: $data['reference_number'],
            reason: $data['reason'],
            currency_id: $data['currency_id'], // This line will no longer cause an error
            original_invoice_id: $data['original_invoice_id'] ?? null,
            original_vendor_bill_id: $data['original_vendor_bill_id'] ?? null,
            lines: $lineDTOs
        );

        return app(CreateAdjustmentDocumentAction::class)->execute($dto);
    }
}

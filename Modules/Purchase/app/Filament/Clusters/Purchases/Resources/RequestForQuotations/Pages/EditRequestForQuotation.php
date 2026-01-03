<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource;

class EditRequestForQuotation extends EditRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['lines'] = $this->getRecord()->lines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit' => $line->unit,
                'unit_price' => $line->unit_price->getAmount()->toFloat(),
                'tax_id' => $line->tax_id,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var \Modules\Purchase\Models\RequestForQuotation $record */
        $lines = collect($data['lines'] ?? [])->map(function ($lineData) use ($record) {
            // Use currency from record (or data if changed)
            $currencyCode = $record->currency->code; // Assuming currency not editable or handle change

            return new \Modules\Purchase\DataTransferObjects\Purchases\CreateRFQLineDTO(
                description: $lineData['description'],
                quantity: (float) $lineData['quantity'],
                product: isset($lineData['product_id']) ? \Modules\Product\Models\Product::find($lineData['product_id']) : null,
                tax: isset($lineData['tax_id']) ? \Modules\Accounting\Models\Tax::find($lineData['tax_id']) : null,
                unit: $lineData['unit'] ?? null,
                unitPrice: isset($lineData['unit_price']) ? \Brick\Money\Money::of($lineData['unit_price'], $currencyCode) : null,
            );
        })->all();

        $dto = new \Modules\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO(
            rfqId: $record->id,
            vendorId: $data['vendor_id'] ?? $record->vendor_id,
            currencyId: $data['currency_id'] ?? $record->currency_id,
            rfqDate: isset($data['rfq_date']) ? \Carbon\Carbon::parse($data['rfq_date']) : null,
            validUntil: isset($data['valid_until']) ? \Carbon\Carbon::parse($data['valid_until']) : null,
            notes: $data['notes'] ?? null,
            exchangeRate: null, // Handle if field exists
            lines: $lines,
        );

        // Add rfq property to DTO or Action accepts ID?
        // Action accepts DTO. DTO has ID.
        // Wait, Action expects DTO to have `rfq` property?
        // UpdatePurchaseOrderAction expects DTO->purchaseOrder (Model).
        // My UpdateRequestForQuotationAction (Step 1048) used `$dto->rfq`.
        // UpdateRFQDTO (Step 1049) DOES NOT HAVE `rfq` property. It has `rfqId`.

        // I MUST FIX Action or DTO.
        // I will fix DTO to include `rfq` model? Or Action to find it?
        // Let's modify Action to find RFQ using ID from DTO.
        // Or better, EditRecord already gives me `$record`.

        // I will pass `$record` to DTO? Or modifying DTO is cleaner.
        // I'll check Action Step 1048 again.
        // It uses `$dto->rfq`.

        return app(\Modules\Purchase\Actions\Purchases\UpdateRequestForQuotationAction::class)->execute($dto);
    }
}

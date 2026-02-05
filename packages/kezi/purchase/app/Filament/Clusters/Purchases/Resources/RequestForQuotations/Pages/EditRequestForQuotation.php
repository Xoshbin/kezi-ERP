<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource;

/**
 * @extends EditRecord<\Kezi\Purchase\Models\RequestForQuotation>
 */
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
        /** @var \Kezi\Purchase\Models\RequestForQuotation $record */
        $lines = collect($data['lines'] ?? [])->map(function ($lineData) use ($record) {
            // Use currency from record (or data if changed)
            $currencyCode = $record->currency->code; // Assuming currency not editable or handle change

            return new \Kezi\Purchase\DataTransferObjects\Purchases\CreateRFQLineDTO(
                description: $lineData['description'],
                quantity: (float) $lineData['quantity'],
                product: isset($lineData['product_id']) ? \Kezi\Product\Models\Product::find($lineData['product_id']) : null,
                tax: isset($lineData['tax_id']) ? \Kezi\Accounting\Models\Tax::find($lineData['tax_id']) : null,
                unit: $lineData['unit'] ?? null,
                unitPrice: isset($lineData['unit_price']) ? \Brick\Money\Money::of($lineData['unit_price'], $currencyCode) : null,
            );
        })->all();

        $dto = new \Kezi\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO(
            rfqId: $record->id,
            vendorId: $data['vendor_id'] ?? $record->vendor_id,
            currencyId: $data['currency_id'] ?? $record->currency_id,
            rfqDate: isset($data['rfq_date']) ? \Carbon\Carbon::parse($data['rfq_date']) : null,
            validUntil: isset($data['valid_until']) ? \Carbon\Carbon::parse($data['valid_until']) : null,
            notes: $data['notes'] ?? null,
            exchangeRate: null, // Handle if field exists
            lines: $lines,
        );

        return app(\Kezi\Purchase\Actions\Purchases\UpdateRequestForQuotationAction::class)->execute($dto);
    }
}

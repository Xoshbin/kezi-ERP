<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateRFQDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateRFQLineDTO;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource;
use Kezi\Purchase\Services\RequestForQuotationService;

/**
 * @extends CreateRecord<\Kezi\Purchase\Models\RequestForQuotation>
 */
class CreateRequestForQuotation extends CreateRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $service = app(RequestForQuotationService::class);

        $currencyId = $data['currency_id'];
        /** @var \Kezi\Foundation\Models\Currency $currency */
        $currency = \Kezi\Foundation\Models\Currency::findOrFail($currencyId);
        $currencyCode = $currency->code;

        $dtos = [];
        if (isset($data['lines'])) {
            foreach ($data['lines'] as $lineData) {
                $product = \Kezi\Product\Models\Product::find($lineData['product_id']);
                $tax = isset($lineData['tax_id']) ? \Kezi\Accounting\Models\Tax::find($lineData['tax_id']) : null;
                $unitPrice = isset($lineData['unit_price']) ? \Brick\Money\Money::of($lineData['unit_price'], $currencyCode) : null;

                $dtos[] = new CreateRFQLineDTO(
                    description: $lineData['description'],
                    quantity: (float) $lineData['quantity'],
                    product: $product,
                    tax: $tax,
                    unit: $lineData['unit'] ?? null,
                    unitPrice: $unitPrice,
                );
            }
        }

        $dto = new CreateRFQDTO(
            companyId: $data['company_id'],
            vendorId: $data['vendor_id'],
            currencyId: $currencyId,
            rfqDate: \Carbon\Carbon::parse($data['rfq_date']),
            validUntil: isset($data['valid_until']) ? \Carbon\Carbon::parse($data['valid_until']) : null,
            notes: $data['notes'] ?? null,
            exchangeRate: $data['exchange_rate'] ?? 1.0,
            createdByUserId: auth()->id(),
            lines: $dtos,
        );

        return $service->createRFQ($dto);
    }
}

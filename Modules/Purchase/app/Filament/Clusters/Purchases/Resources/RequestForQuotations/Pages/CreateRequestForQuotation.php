<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Purchase\DataTransferObjects\Purchases\CreateRFQDTO;
use Modules\Purchase\DataTransferObjects\Purchases\CreateRFQLineDTO;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource;
use Modules\Purchase\Services\RequestForQuotationService;

class CreateRequestForQuotation extends CreateRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $service = app(RequestForQuotationService::class);

        $dtos = [];
        if (isset($data['lines'])) {
            // Note: In Form, we might handle product/tax loading if needed,
            // but DTO expects IDs or objects.
            // CreateRFQLineDTO expects objects or handled in Action.
            // Let's adapt data to DTO.
            foreach ($data['lines'] as $lineData) {
                // Here we might need to look up product/tax models if DTO strictly requires models
                // or update DTO to accept IDs.
                // Assuming we update DTO or handle lookup here.
                // For valid DTO usage:
                $product = \Modules\Product\Models\Product::find($lineData['product_id']);
                $tax = isset($lineData['tax_id']) ? \Modules\Accounting\Models\Tax::find($lineData['tax_id']) : null;
                $unitPrice = isset($lineData['unit_price']) ? \Brick\Money\Money::of($lineData['unit_price'], $data['currency_code'] ?? 'USD') : null; // Currency handling needed

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

        $currencyId = $data['currency_id'];
        $currency = \Modules\Foundation\Models\Currency::find($currencyId);

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

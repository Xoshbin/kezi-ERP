<?php

namespace Jmeryar\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages;

use Brick\Money\Money;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Sales\DataTransferObjects\Sales\CreateQuoteDTO;
use Jmeryar\Sales\DataTransferObjects\Sales\CreateQuoteLineDTO;
use Jmeryar\Sales\Filament\Clusters\Sales\Resources\Quotes\QuoteResource;
use Jmeryar\Sales\Services\QuoteService;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $currency = Currency::find($data['currency_id']);
        $currencyCode = $currency?->code ?? 'IQD';

        // Build line DTOs
        $linesDtos = [];
        foreach ($data['lines'] ?? [] as $index => $line) {
            $unitPrice = Money::of($line['unit_price'] ?? 0, $currencyCode);

            $linesDtos[] = new CreateQuoteLineDTO(
                description: $line['description'] ?? '',
                quantity: (float) ($line['quantity'] ?? 1),
                unitPrice: $unitPrice,
                productId: $line['product_id'] ?? null,
                taxId: $line['tax_id'] ?? null,
                incomeAccountId: $line['income_account_id'] ?? null,
                unit: $line['unit'] ?? null,
                discountPercentage: (float) ($line['discount_percentage'] ?? 0),
                lineOrder: $index,
            );
        }

        // Create DTO
        $dto = new CreateQuoteDTO(
            companyId: Filament::getTenant()->id,
            partnerId: $data['partner_id'],
            currencyId: $data['currency_id'],
            quoteDate: \Carbon\Carbon::parse($data['quote_date']),
            validUntil: \Carbon\Carbon::parse($data['valid_until']),
            lines: $linesDtos,
            notes: $data['notes'] ?? null,
            termsAndConditions: $data['terms_and_conditions'] ?? null,
            exchangeRate: (float) ($data['exchange_rate'] ?? 1),
            createdByUserId: auth()->id(),
        );

        // Use QuoteService to create
        $quote = app(QuoteService::class)->create($dto);

        Notification::make()
            ->success()
            ->title(__('sales::quote.notifications.created'))
            ->send();

        return $quote;
    }
}

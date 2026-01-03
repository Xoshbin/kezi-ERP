<?php

namespace Modules\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages;

use Brick\Money\Money;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\Foundation\Models\Currency;
use Modules\Sales\DataTransferObjects\Sales\UpdateQuoteDTO;
use Modules\Sales\DataTransferObjects\Sales\UpdateQuoteLineDTO;
use Modules\Sales\Filament\Clusters\Sales\Resources\Quotes\QuoteResource;
use Modules\Sales\Services\QuoteService;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Transform lines for the form
        $quote = $this->record;
        $lines = [];

        foreach ($quote->lines as $line) {
            $lines[] = [
                'id' => $line->id,
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price?->getAmount()?->toFloat() ?? 0,
                'discount_percentage' => $line->discount_percentage,
                'tax_id' => $line->tax_id,
                'unit' => $line->unit,
            ];
        }

        $data['lines'] = $lines;
        $data['subtotal'] = $quote->subtotal?->getAmount()?->toFloat() ?? 0;
        $data['discount_total'] = $quote->discount_total?->getAmount()?->toFloat() ?? 0;
        $data['tax_total'] = $quote->tax_total?->getAmount()?->toFloat() ?? 0;
        $data['total'] = $quote->total?->getAmount()?->toFloat() ?? 0;

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $currency = Currency::find($data['currency_id']);
        $currencyCode = $currency?->code ?? 'IQD';

        // Build line DTOs
        $linesDtos = [];
        foreach ($data['lines'] ?? [] as $index => $line) {
            $unitPrice = Money::of($line['unit_price'] ?? 0, $currencyCode);

            $linesDtos[] = new UpdateQuoteLineDTO(
                lineId: $line['id'] ?? null,
                description: $line['description'] ?? null,
                quantity: isset($line['quantity']) ? (float) $line['quantity'] : null,
                unitPrice: $unitPrice,
                productId: $line['product_id'] ?? null,
                taxId: $line['tax_id'] ?? null,
                incomeAccountId: $line['income_account_id'] ?? null,
                unit: $line['unit'] ?? null,
                discountPercentage: isset($line['discount_percentage']) ? (float) $line['discount_percentage'] : null,
                lineOrder: $index,
            );
        }

        // Create DTO
        $dto = new UpdateQuoteDTO(
            quoteId: $record->id,
            partnerId: $data['partner_id'] ?? null,
            currencyId: $data['currency_id'] ?? null,
            quoteDate: isset($data['quote_date']) ? \Carbon\Carbon::parse($data['quote_date']) : null,
            validUntil: isset($data['valid_until']) ? \Carbon\Carbon::parse($data['valid_until']) : null,
            notes: $data['notes'] ?? null,
            termsAndConditions: $data['terms_and_conditions'] ?? null,
            exchangeRate: isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
            lines: $linesDtos,
        );

        // Use QuoteService to update
        $quote = app(QuoteService::class)->update($dto);

        Notification::make()
            ->success()
            ->title(__('sales::quote.notifications.updated'))
            ->send();

        return $quote;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

<?php

namespace App\Filament\Clusters\Sales\Resources\SalesOrders\Pages;

use App\Actions\Sales\CreateSalesOrderAction;
use App\DataTransferObjects\Sales\CreateSalesOrderDTO;
use App\DataTransferObjects\Sales\CreateSalesOrderLineDTO;
use App\Filament\Clusters\Sales\Resources\SalesOrders\SalesOrderResource;
use Brick\Money\Money;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Convert lines data to DTOs
        $lineDtos = [];
        foreach ($data['lines'] ?? [] as $lineData) {
            $lineDtos[] = new CreateSalesOrderLineDTO(
                product_id: $lineData['product_id'],
                description: $lineData['description'],
                quantity: (float) $lineData['quantity'],
                unit_price: Money::ofMinor($lineData['unit_price'], $this->getCurrencyCode($data)),
                tax_id: $lineData['tax_id'] ?? null,
                expected_delivery_date: $lineData['expected_delivery_date'] ? Carbon::parse($lineData['expected_delivery_date']) : null,
                notes: $lineData['notes'] ?? null,
            );
        }

        // Create the main DTO
        $dto = new CreateSalesOrderDTO(
            company_id: $data['company_id'],
            customer_id: $data['customer_id'],
            currency_id: $data['currency_id'],
            created_by_user_id: $data['created_by_user_id'],
            reference: $data['reference'] ?? null,
            so_date: Carbon::parse($data['so_date']),
            expected_delivery_date: $data['expected_delivery_date'] ? Carbon::parse($data['expected_delivery_date']) : null,
            exchange_rate_at_creation: $data['exchange_rate_at_creation'] ?? null,
            notes: $data['notes'] ?? null,
            terms_and_conditions: $data['terms_and_conditions'] ?? null,
            delivery_location_id: $data['delivery_location_id'] ?? null,
            lines: $lineDtos,
        );

        // Execute the action
        $action = app(CreateSalesOrderAction::class);
        return $action->execute($dto);
    }

    private function getCurrencyCode(array $data): string
    {
        $currencyId = $data['currency_id'];
        $currency = \App\Models\Currency::find($currencyId);
        return $currency ? $currency->code : 'USD';
    }
}

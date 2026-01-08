<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages;

use Brick\Money\Money;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource;
use Modules\Foundation\Enums\Incoterm;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Foundation\Models\Currency;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currency = Currency::findOrFail($data['currency_id']);
        // Ensure we have a single Currency model, not a collection
        if ($currency instanceof Collection) {
            $currency = $currency->first();
            if (! $currency) {
                throw new InvalidArgumentException('Currency not found');
            }
        }
        $lineDTOs = [];
        foreach ($data['invoiceLines'] as $line) {
            $lineDTOs[] = new CreateInvoiceLineDTO(
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: Money::of($line['unit_price'], $currency->code),
                income_account_id: $line['income_account_id'],
                product_id: $line['product_id'] ?? null,
                tax_id: $line['tax_id'] ?? null
            );
        }
        $data['invoiceLines'] = $lineDTOs;
        $data['created_by_user_id'] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Store exchange_rate_at_creation separately since it's not in the DTO
        $exchangeRate = $data['exchange_rate_at_creation'] ?? null;

        $invoiceDTO = new CreateInvoiceDTO(
            company_id: (int) (Filament::getTenant()->id ?? 0),
            customer_id: $data['customer_id'],
            currency_id: $data['currency_id'],
            invoice_date: $data['invoice_date'],
            due_date: $data['due_date'],
            lines: $data['invoiceLines'],
            fiscal_position_id: $data['fiscal_position_id'] ?? null,
            incoterm: isset($data['incoterm']) ? Incoterm::tryFrom($data['incoterm']) : null,
        );

        $invoice = app(\Modules\Sales\Actions\Sales\CreateInvoiceAction::class)->execute($invoiceDTO);

        // Set exchange_rate_at_creation if provided
        if ($exchangeRate) {
            $invoice->update([
                'exchange_rate_at_creation' => $exchangeRate,
            ]);
        }

        return $invoice;
    }

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('customer-invoices'),
        ];
    }
}

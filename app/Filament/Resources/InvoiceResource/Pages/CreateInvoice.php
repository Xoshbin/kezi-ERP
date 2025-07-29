<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Actions\Sales\CreateInvoiceAction;
use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoiceLines'] = $data['invoiceLines'] ?? [];
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $lineDTOs = [];
        foreach ($data['invoiceLines'] as $line) {
            $lineDTOs[] = new CreateInvoiceLineDTO(
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: $line['unit_price'],
                income_account_id: $line['income_account_id'],
                product_id: $line['product_id'] ?? null,
                tax_id: $line['tax_id'] ?? null
            );
        }

        $invoiceDTO = new CreateInvoiceDTO(
            company_id: $data['company_id'],
            customer_id: $data['customer_id'],
            currency_id: $data['currency_id'],
            invoice_date: $data['invoice_date'],
            due_date: $data['due_date'],
            lines: $lineDTOs,
            fiscal_position_id: $data['fiscal_position_id'] ?? null
        );

        return (new CreateInvoiceAction())->execute($invoiceDTO);
    }
}

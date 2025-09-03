<?php

namespace App\Filament\Clusters\Accounting\Resources\Invoices\Pages;

use App\Actions\Sales\CreateInvoiceAction;
use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource;
use App\Models\Currency;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currency = Currency::find($data['currency_id']);
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
        $invoiceDTO = new CreateInvoiceDTO(
            company_id: Filament::getTenant()->id,
            customer_id: $data['customer_id'],
            currency_id: $data['currency_id'],
            invoice_date: $data['invoice_date'],
            due_date: $data['due_date'],
            lines: $data['invoiceLines'],
            fiscal_position_id: $data['fiscal_position_id'] ?? null
        );

        return app(CreateInvoiceAction::class)->execute($invoiceDTO);
    }
}

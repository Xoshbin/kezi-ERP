<?php

namespace App\Filament\Resources\VendorBillResource\Pages;

use App\Actions\Purchases\CreateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Filament\Resources\VendorBillResource;
use App\Models\Tax;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVendorBill extends CreateRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['lines'] = $data['lines'] ?? [];

        // Calculate totals right before creation, mirroring the logic from the Repeater.
        $totalAmount = 0;
        $totalTax = 0;
        foreach ($data['lines'] as $line) {
            $quantity = (float)($line['quantity'] ?? 0);
            $unitPrice = (float)($line['unit_price'] ?? 0);
            $subtotal = $quantity * $unitPrice;
            $lineTax = 0;
            if (!empty($line['tax_id'])) {
                $tax = Tax::find($line['tax_id']);
                if ($tax) {
                    $lineTax = $subtotal * $tax->rate;
                }
            }
            $totalTax += $lineTax;
            $totalAmount += $subtotal + $lineTax;
        }
        $data['total_amount'] = $totalAmount;
        $data['total_tax'] = $totalTax;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new CreateVendorBillLineDTO(
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: $line['unit_price'],
                expense_account_id: $line['expense_account_id'],
                product_id: $line['product_id'] ?? null,
                tax_id: $line['tax_id'] ?? null,
                analytic_account_id: $line['analytic_account_id'] ?? null
            );
        }

        $vendorBillDTO = new CreateVendorBillDTO(
            company_id: $data['company_id'],
            vendor_id: $data['vendor_id'],
            currency_id: $data['currency_id'],
            bill_reference: $data['bill_reference'],
            bill_date: $data['bill_date'],
            accounting_date: $data['accounting_date'],
            due_date: $data['due_date'] ?? null,
            lines: $lineDTOs
        );

        return app(CreateVendorBillAction::class)->execute($vendorBillDTO);
    }
}

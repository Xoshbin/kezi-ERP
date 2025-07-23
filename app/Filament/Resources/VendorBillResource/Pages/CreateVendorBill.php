<?php

namespace App\Filament\Resources\VendorBillResource\Pages;

use App\Filament\Resources\VendorBillResource;
use App\Services\VendorBillService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateVendorBill extends CreateRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lines = $this->form->getState()['lines'] ?? [];

        $totalAmount = 0;
        $totalTax = 0;

        foreach ($lines as $line) {
            $quantity = $line['quantity'] ?? 0;
            $unitPrice = $line['unit_price'] ?? 0;
            $subtotal = $quantity * $unitPrice;
            $totalLineTax = $line['total_line_tax'] ?? 0;

            $totalAmount += $subtotal + $totalLineTax;
            $totalTax += $totalLineTax;
        }

        $data['total_amount'] = $totalAmount;
        $data['total_tax'] = $totalTax;

        return $data;
    }
}

<?php

namespace App\Filament\Resources\VendorBillResource\Pages;

use App\Actions\Purchases\CreateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Filament\Resources\VendorBillResource;
use App\Models\Currency;
use Brick\Money\Money;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateVendorBill extends CreateRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currency = Currency::find($data['currency_id']);
        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new CreateVendorBillLineDTO(
                product_id: $line['product_id'],
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: Money::of($line['unit_price'], $currency->code),
                expense_account_id: $line['expense_account_id'],
                tax_id: $line['tax_id'] ?? null,
                analytic_account_id: $line['analytic_account_id'] ?? null,
                currency: $currency->code
            );
        }
        $data['lines'] = $lineDTOs;
        $data['created_by_user_id'] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $vendorBillDTO = new CreateVendorBillDTO(...$data);

        return app(CreateVendorBillAction::class)->execute($vendorBillDTO);
    }
}

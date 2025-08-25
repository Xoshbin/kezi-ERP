<?php

namespace App\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use App\Actions\Purchases\CreateVendorBillAction;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use App\Models\Currency;
use App\Models\VendorBillAttachment;
use Brick\Money\Money;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CreateVendorBill extends CreateRecord
{
    protected static string $resource = VendorBillResource::class;

    protected array $attachments = [];

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
        // Store attachments separately and remove from data for DTO
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments']);

        // Add company_id from tenant context
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        $vendorBillDTO = new CreateVendorBillDTO(...$data);
        $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);

        // Store attachments for later processing
        $this->attachments = $attachments;

        return $vendorBill;
    }

    protected function afterCreate(): void
    {
        $this->handleFileUploads();
    }

    protected function handleFileUploads(): void
    {
        if (empty($this->attachments)) {
            return;
        }

        foreach ($this->attachments as $filePath) {
            if (Storage::disk('local')->exists($filePath)) {
                $fileInfo = pathinfo($filePath);
                $mimeType = Storage::disk('local')->mimeType($filePath);
                $fileSize = Storage::disk('local')->size($filePath);

                VendorBillAttachment::create([
                    'company_id' => $this->record->company_id,
                    'vendor_bill_id' => $this->record->id,
                    'file_name' => $fileInfo['basename'],
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'uploaded_by_user_id' => Auth::id(),
                ]);
            }
        }
    }
}

<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $invoiceService = new InvoiceService();
        // The update method in the service handles creation of lines and recalculation.
        $invoice = static::getModel()::create(collect($data)->except('lines')->all());
        $invoiceService->update($invoice, ['lines' => $data['lines'] ?? []]);
        return $invoice;
    }
}

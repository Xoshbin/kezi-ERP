<?php

namespace App\Filament\Resources\RecurringInvoiceResource\Pages;

use App\Filament\Resources\RecurringInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecurringInvoice extends EditRecord
{
    protected static string $resource = RecurringInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert template_data lines back to form format
        if (isset($data['template_data']['lines'])) {
            $data['lines'] = array_map(function ($line) {
                return [
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price']['amount'] ?? 0,
                    'product_id' => $line['product_id'] ?? null,
                    'tax_id' => $line['tax_id'] ?? null,
                ];
            }, $data['template_data']['lines']);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert lines back to template_data format
        if (isset($data['lines'])) {
            $currency = $this->record->currency;
            $templateData = [
                'lines' => array_map(function ($line) use ($currency) {
                    return [
                        'description' => $line['description'],
                        'quantity' => $line['quantity'],
                        'unit_price' => [
                            'amount' => $line['unit_price'],
                            'currency' => $currency->code,
                        ],
                        'product_id' => $line['product_id'] ?? null,
                        'tax_id' => $line['tax_id'] ?? null,
                    ];
                }, $data['lines']),
            ];
            
            $data['template_data'] = $templateData;
            unset($data['lines']);
        }

        // Set updated_by_user_id
        $data['updated_by_user_id'] = auth()->id();

        return $data;
    }
}

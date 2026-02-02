<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource;

class ViewRequestForQuotation extends ViewRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('send')
                ->label(__('purchase::request_for_quotation.actions.send_to_vendor'))
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->visible(fn (RequestForQuotationResource $resource, $record) => $record->status === \Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus::Draft)
                ->action(fn ($record, \Kezi\Purchase\Services\RequestForQuotationService $service) => $service->sendRFQ($record)),

            Actions\Action::make('record_bid')
                ->label(__('purchase::request_for_quotation.actions.record_bid'))
                ->icon('heroicon-o-clipboard-document-check')
                ->visible(fn ($record) => in_array($record->status, [\Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus::Sent, \Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus::BidReceived]))
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')->label(__('purchase::request_for_quotation.fields.bid_notes')),
                    // Could add price updates here for bulk or simple updates
                ])
                ->action(function ($record, array $data, \Kezi\Purchase\Services\RequestForQuotationService $service) {
                    $dto = new \Kezi\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO(
                        rfqId: $record->id,
                        notes: $data['notes']
                    );
                    $service->recordBid($record, $dto);
                }),

            Actions\Action::make('convert_to_po')
                ->label(__('purchase::request_for_quotation.actions.convert_to_order'))
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn ($record) => in_array($record->status, [\Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus::BidReceived, \Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus::Accepted]) && ! $record->converted_to_purchase_order_id)
                ->form([
                    \Filament\Forms\Components\DatePicker::make('po_date')->default(now())->required(),
                    \Filament\Forms\Components\DatePicker::make('expected_delivery_date'),
                    \Filament\Forms\Components\TextInput::make('reference')->label(__('purchase::request_for_quotation.fields.vendor_reference')),
                ])
                ->action(function ($record, array $data, \Kezi\Purchase\Services\RequestForQuotationService $service) {
                    $dto = new \Kezi\Purchase\DataTransferObjects\Purchases\ConvertRFQToPurchaseOrderDTO(
                        rfqId: $record->id,
                        poDate: \Carbon\Carbon::parse($data['po_date']),
                        expectedDeliveryDate: isset($data['expected_delivery_date']) ? \Carbon\Carbon::parse($data['expected_delivery_date']) : null,
                        reference: $data['reference'] ?? null,
                    );
                    $service->convertToPurchaseOrder($dto);

                    \Filament\Notifications\Notification::make()
                        ->title(__('purchase::request_for_quotation.notifications.po_created_success'))
                        ->success()
                        ->send();

                    return redirect()->to(RequestForQuotationResource::getUrl('view', ['record' => $record->id]));
                }),
        ];
    }
}

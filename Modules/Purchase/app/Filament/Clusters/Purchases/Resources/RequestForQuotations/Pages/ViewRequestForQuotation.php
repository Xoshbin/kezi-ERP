<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource;

class ViewRequestForQuotation extends ViewRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('send')
                ->label('Send to Vendor')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->visible(fn (RequestForQuotationResource $resource, $record) => $record->status === \Modules\Purchase\Enums\Purchases\RequestForQuotationStatus::Draft)
                ->action(fn ($record, \Modules\Purchase\Services\RequestForQuotationService $service) => $service->sendRFQ($record)),

            Actions\Action::make('record_bid')
                ->label('Record Bid')
                ->icon('heroicon-o-clipboard-document-check')
                ->visible(fn ($record) => in_array($record->status, [\Modules\Purchase\Enums\Purchases\RequestForQuotationStatus::Sent, \Modules\Purchase\Enums\Purchases\RequestForQuotationStatus::BidReceived]))
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')->label('Bid Notes'),
                    // Could add price updates here for bulk or simple updates
                ])
                ->action(function ($record, array $data, \Modules\Purchase\Services\RequestForQuotationService $service) {
                    $dto = new \Modules\Purchase\DataTransferObjects\Purchases\UpdateRFQDTO(
                        rfqId: $record->id,
                        notes: $data['notes']
                    );
                    $service->recordBid($record, $dto);
                }),

            Actions\Action::make('convert_to_po')
                ->label('Convert to Order')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn ($record) => in_array($record->status, [\Modules\Purchase\Enums\Purchases\RequestForQuotationStatus::BidReceived, \Modules\Purchase\Enums\Purchases\RequestForQuotationStatus::Accepted]) && ! $record->converted_to_purchase_order_id)
                ->form([
                    \Filament\Forms\Components\DatePicker::make('po_date')->default(now())->required(),
                    \Filament\Forms\Components\DatePicker::make('expected_delivery_date'),
                    \Filament\Forms\Components\TextInput::make('reference')->label('Vendor Reference'),
                ])
                ->action(function ($record, array $data, \Modules\Purchase\Services\RequestForQuotationService $service) {
                    $dto = new \Modules\Purchase\DataTransferObjects\Purchases\ConvertRFQToPurchaseOrderDTO(
                        rfqId: $record->id,
                        poDate: \Carbon\Carbon::parse($data['po_date']),
                        expectedDeliveryDate: isset($data['expected_delivery_date']) ? \Carbon\Carbon::parse($data['expected_delivery_date']) : null,
                        reference: $data['reference'] ?? null,
                    );
                    $service->convertToPurchaseOrder($dto);

                    \Filament\Notifications\Notification::make()
                        ->title('Purchase Order created successfully')
                        ->success()
                        ->send();

                    return redirect()->to(RequestForQuotationResource::getUrl('view', ['record' => $record->id]));
                }),
        ];
    }
}

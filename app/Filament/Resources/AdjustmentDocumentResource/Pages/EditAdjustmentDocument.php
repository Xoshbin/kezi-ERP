<?php

namespace App\Filament\Resources\AdjustmentDocumentResource\Pages;

use App\Actions\Adjustments\UpdateAdjustmentDocumentAction;
use App\DataTransferObjects\Adjustments\UpdateAdjustmentDocumentDTO;
use App\Filament\Resources\AdjustmentDocumentResource;
use App\Models\AdjustmentDocument;
use App\Services\AdjustmentDocumentService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAdjustmentDocument extends EditRecord
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('post')
                ->label(__('adjustment_document.post_document'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (AdjustmentDocument $record): bool => $record->status === AdjustmentDocument::STATUS_DRAFT)
                ->action(function (AdjustmentDocument $record): void {
                    $this->save(); // Save any pending form changes first
                    $service = app(AdjustmentDocumentService::class);
                    try {
                        $service->post($record, auth()->user());
                        Notification::make()->title(__('adjustment_document.notification_document_posted_successfully'))->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title(__('adjustment_document.notification_document_post_error'))->body($e->getMessage())->danger()->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $dto = new UpdateAdjustmentDocumentDTO(
            adjustmentDocument: $record,
            type: $data['type'],
            date: $data['date'],
            reference_number: $data['reference_number'],
            total_amount: $data['total_amount'],
            total_tax: $data['total_tax'],
            reason: $data['reason'],
            currency_id: $data['currency_id'],
            original_invoice_id: $data['original_invoice_id'] ?? null,
            original_vendor_bill_id: $data['original_vendor_bill_id'] ?? null,
        );

        return (new UpdateAdjustmentDocumentAction())->execute($dto);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['total_amount'] = $this->record->total_amount?->getAmount()->toFloat();
        $data['total_tax'] = $this->record->total_tax?->getAmount()->toFloat();
        return $data;
    }
}

<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label(__('payment.edit.action.confirm.label'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(Payment $record): bool => $record->status === Payment::STATUS_DRAFT)
                ->action(function (Payment $record): void {
                    $this->save();
                    $service = app(PaymentService::class);
                    try {
                        $service->confirm($record, Auth::user());
                        Notification::make()->title(__('payment.action.confirm.notification.success'))->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title(__('payment.action.confirm.notification.error'))->body($e->getMessage())->danger()->send();
                    }
                }),
            Actions\Action::make('cancel')
                ->label('Cancel Payment')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (Payment $record) {
                    try {
                        app(PaymentService::class)->cancel($record, Auth::user());
                        Notification::make()
                            ->title('Payment Cancelled')
                            ->body('The payment and its journal entry have been successfully reversed.')
                            ->success()
                            ->send();
                        // Refresh the page to show the new 'Cancelled' status
                        $this->refreshFormData(['status']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Cancellation Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                // Only show this button for Confirmed payments
                ->visible(fn(Payment $record): bool => $record->status === Payment::STATUS_CONFIRMED),
            Actions\DeleteAction::make()
                ->visible(fn(Payment $record): bool => $record->status === Payment::STATUS_DRAFT),
        ];
    }

    /**
     * This method loads the existing linked documents from the relationship
     * and formats them into an array that the 'document_links' Repeater can understand.
     */
    // In app/Filament/Resources/PaymentResource/Pages/EditPayment.php

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // ... (The existing logic for loading 'document_links' is correct)
        $this->record->loadMissing('paymentDocumentLinks');

        $linksData = [];
        foreach ($this->record->paymentDocumentLinks as $link) {
            $documentType = null;
            $documentId = null;

            if ($link->invoice_id) {
                $documentType = 'invoice';
                $documentId = $link->invoice_id;
            } elseif ($link->vendor_bill_id) {
                $documentType = 'vendor_bill';
                $documentId = $link->vendor_bill_id;
            }

            if ($documentType) {
                $linksData[] = [
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                    'amount_applied' => $link->amount_applied?->getAmount()->toFloat(),
                ];
            }
        }
        $data['document_links'] = $linksData;

        // THE FIX: Manually set the 'amount' field from the record's Money object.
        $data['amount'] = $this->record->amount?->getAmount()->toFloat();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // For now, we only allow updating the main fields of a draft payment,
        // not the linked documents. A full update would require an UpdatePaymentAction.
        $record->update($data);
        return $record;
    }
}

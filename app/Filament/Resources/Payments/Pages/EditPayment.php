<?php

namespace App\Filament\Resources\Payments\Pages;

use Filament\Actions\Action;
use Exception;
use Filament\Actions\DeleteAction;
use App\Actions\Payments\UpdatePaymentAction;
use App\DataTransferObjects\Payments\UpdatePaymentDTO;
use App\DataTransferObjects\Payments\UpdatePaymentDocumentLinkDTO;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Currency;
use App\Models\Payment;
use App\Enums\Payments\PaymentStatus;
use App\Services\PaymentService;
use Brick\Money\Money;
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
            Action::make('confirm')
                ->label(__('payment.edit.action.confirm.label'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(Payment $record): bool => $record->status === PaymentStatus::Draft)
                ->action(function (Payment $record): void {
                    $this->save();
                    $service = app(PaymentService::class);
                    try {
                        $service->confirm($record, Auth::user());
                        Notification::make()->title(__('payment.action.confirm.notification.success'))->success()->send();
                    } catch (Exception $e) {
                        Notification::make()->title(__('payment.action.confirm.notification.error'))->body($e->getMessage())->danger()->send();
                    }
                }),
            // Actions\Action::make('cancel')
            //     ->label('Cancel Payment')
            //     ->color('danger')
            //     ->requiresConfirmation()
            //     ->action(function (Payment $record) {
            //         try {
            //             app(PaymentService::class)->cancel($record, Auth::user(), 'Payment cancelled via UI');
            //             Notification::make()
            //                 ->title('Payment Cancelled')
            //                 ->body('The payment and its journal entry have been successfully reversed.')
            //                 ->success()
            //                 ->send();
            //             // Refresh the page to show the new 'Cancelled' status
            //             $this->refreshFormData(['status']);
            //         } catch (\Exception $e) {
            //             Notification::make()
            //                 ->title('Cancellation Failed')
            //                 ->body($e->getMessage())
            //                 ->danger()
            //                 ->send();
            //         }
            //     })
            //     // Only show this button for Confirmed payments
            //     ->visible(fn(Payment $record): bool => $record->status === PaymentStatus::Confirmed),
            DeleteAction::make()
                ->visible(fn(Payment $record): bool => $record->status === PaymentStatus::Draft),
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
        $currency = Currency::find($data['currency_id']);
        $linkDTOs = [];

        // If document_links is not in the data (e.g., for confirmed payments where it's disabled),
        // use the existing links from the record
        $documentLinks = $data['document_links'] ?? [];
        if (empty($documentLinks) && $record->paymentDocumentLinks->isNotEmpty()) {
            foreach ($record->paymentDocumentLinks as $link) {
                $documentLinks[] = [
                    'document_type' => $link->invoice_id ? 'invoice' : 'vendor_bill',
                    'document_id' => $link->invoice_id ?: $link->vendor_bill_id,
                    'amount_applied' => $link->amount_applied->getAmount()->toFloat(),
                ];
            }
        }

        foreach ($documentLinks as $link) {
            $linkDTOs[] = new UpdatePaymentDocumentLinkDTO(
                document_type: $link['document_type'],
                document_id: $link['document_id'],
                amount_applied: Money::of($link['amount_applied'], $currency->code)
            );
        }

        $paymentDTO = new UpdatePaymentDTO(
            payment: $record,
            company_id: \Filament\Facades\Filament::getTenant()->id,
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            payment_date: $data['payment_date'],
            document_links: $linkDTOs,
            reference: $data['reference'],
            updated_by_user_id: Auth::id()
        );

        return app(UpdatePaymentAction::class)->execute($paymentDTO);
    }
}

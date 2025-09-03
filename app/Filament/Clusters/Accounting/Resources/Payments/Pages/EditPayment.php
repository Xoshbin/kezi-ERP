<?php

namespace App\Filament\Clusters\Accounting\Resources\Payments\Pages;

use App\Actions\Payments\UpdatePaymentAction;
use App\DataTransferObjects\Payments\UpdatePaymentDTO;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use App\Models\Currency;
use App\Models\Payment;
use App\Services\PaymentService;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
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
                ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Draft)
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
                ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Draft),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Set the 'amount' field from the record's Money object for standalone payments
        $data['amount'] = $this->record->amount?->getAmount()->toFloat();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $currency = Currency::find($data['currency_id']);

        // Prepare amount for standalone payments
        $amount = Money::of($data['amount'], $currency->code);

        $paymentDTO = new UpdatePaymentDTO(
            payment: $record,
            company_id: Filament::getTenant()->id,
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            payment_date: $data['payment_date'],
            payment_purpose: PaymentPurpose::from($data['payment_purpose']),
            payment_type: PaymentType::from($data['payment_type']),
            partner_id: $data['partner_id'],
            amount: $amount,
            counterpart_account_id: $data['counterpart_account_id'],
            document_links: [], // No document links for standalone payments
            reference: $data['reference'],
            updated_by_user_id: Auth::id()
        );

        return app(UpdatePaymentAction::class)->execute($paymentDTO);
    }
}

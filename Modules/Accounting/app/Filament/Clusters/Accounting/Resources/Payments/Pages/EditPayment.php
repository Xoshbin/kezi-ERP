<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages;

use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Foundation\Models\Currency;
use Modules\Payment\Actions\Payments\UpdatePaymentAction;
use Modules\Payment\DataTransferObjects\Payments\UpdatePaymentDTO;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('payments'),
            Action::make('confirm')
                ->label(__('payment.edit.action.confirm.label'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Draft)
                ->action(function (Payment $record): void {
                    $this->save();
                    $service = app(PaymentService::class);
                    try {
                        $user = Auth::user();
                        if (! $user) {
                            throw new Exception('User must be authenticated to confirm payment');
                        }
                        $service->confirm($record, $user);
                        Notification::make()->title(__('payment.action.confirm.notification.success'))->success()->send();
                    } catch (Exception $e) {
                        Notification::make()->title(__('payment.action.confirm.notification.error'))->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('cancel')
                ->label(__('accounting::payment.action.cancel.label'))
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (Payment $record) {
                    try {
                        app(PaymentService::class)->cancel($record, Auth::user(), 'Payment cancelled via UI');
                        Notification::make()
                            ->title(__('accounting::payment.action.cancel.notification.success'))
                            ->body(__('accounting::payment.action.cancel.notification.success_body'))
                            ->success()
                            ->send();
                        // Refresh the page to show the new 'Cancelled' status
                        $this->refreshFormData(['status']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('accounting::payment.action.cancel.notification.error'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                // Only show this button for Confirmed payments
                ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Confirmed),
            DeleteAction::make()
                ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Draft),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Set the 'amount' field from the record's Money object for standalone payments
        $record = $this->getRecord();
        if ($record instanceof Payment) {
            $data['amount'] = $record->amount->getAmount()->toFloat();
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Payment) {
            throw new InvalidArgumentException('Expected Payment record');
        }

        $currency = Currency::findOrFail($data['currency_id']);
        // Ensure we have a single Currency model, not a collection
        if ($currency instanceof Collection) {
            $currency = $currency->first();
            if (! $currency) {
                throw new InvalidArgumentException('Currency not found');
            }
        }

        // Prepare amount for standalone payments
        $amount = Money::of($data['amount'], $currency->code);

        $paymentDTO = new UpdatePaymentDTO(
            payment: $record,
            company_id: $record->company_id,
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            payment_date: $data['payment_date'],
            payment_type: PaymentType::from($data['payment_type']),
            payment_method: PaymentMethod::from($data['payment_method']),
            paid_to_from_partner_id: $data['paid_to_from_partner_id'],
            amount: $amount,
            document_links: [], // No document links for standalone payments
            reference: $data['reference'],
            updated_by_user_id: (int) Auth::id()
        );

        return app(UpdatePaymentAction::class)->execute($paymentDTO);
    }
}

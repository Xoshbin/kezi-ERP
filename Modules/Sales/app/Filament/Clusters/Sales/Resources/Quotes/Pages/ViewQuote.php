<?php

namespace Modules\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Sales\Filament\Clusters\Sales\Resources\Quotes\QuoteResource;
use Modules\Sales\Services\QuoteService;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn ($record) => $record->isEditable()),

            Action::make('send')
                ->label(__('sales::quote.actions.send'))
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status->canBeSent())
                ->action(function ($record) {
                    app(QuoteService::class)->send($record);

                    Notification::make()
                        ->success()
                        ->title(__('sales::quote.notifications.sent'))
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Action::make('accept')
                ->label(__('sales::quote.actions.accept'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status->canBeAccepted())
                ->action(function ($record) {
                    app(QuoteService::class)->accept($record);

                    Notification::make()
                        ->success()
                        ->title(__('sales::quote.notifications.accepted'))
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Action::make('reject')
                ->label(__('sales::quote.actions.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn ($record) => $record->status->canBeRejected())
                ->form([
                    Textarea::make('rejection_reason')
                        ->label(__('sales::quote.modals.reject.reason_label'))
                        ->required()
                        ->rows(3),
                ])
                ->modalHeading(__('sales::quote.modals.reject.heading'))
                ->modalDescription(__('sales::quote.modals.reject.description'))
                ->modalSubmitActionLabel(__('sales::quote.modals.reject.confirm'))
                ->action(function (array $data, $record) {
                    app(QuoteService::class)->reject($record, $data['rejection_reason']);

                    Notification::make()
                        ->success()
                        ->title(__('sales::quote.notifications.rejected'))
                        ->send();

                    $this->refreshFormData(['status', 'rejection_reason']);
                }),

            Action::make('convert_to_order')
                ->label(__('sales::quote.actions.convert_to_order'))
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->canBeConverted())
                ->action(function ($record) {
                    $salesOrder = app(QuoteService::class)->convertToSalesOrder($record, auth()->id());

                    Notification::make()
                        ->success()
                        ->title(__('sales::quote.notifications.converted_to_order'))
                        ->body("Sales Order #{$salesOrder->so_number} created.")
                        ->send();

                    $this->refreshFormData(['status', 'converted_to_sales_order_id']);
                }),

            Action::make('convert_to_invoice')
                ->label(__('sales::quote.actions.convert_to_invoice'))
                ->icon('heroicon-o-document-currency-dollar')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->canBeConverted())
                ->action(function ($record) {
                    $invoice = app(QuoteService::class)->convertToInvoice($record);

                    Notification::make()
                        ->success()
                        ->title(__('sales::quote.notifications.converted_to_invoice'))
                        ->body("Invoice #{$invoice->invoice_number} created.")
                        ->send();

                    $this->refreshFormData(['status', 'converted_to_invoice_id']);
                }),

            Action::make('create_revision')
                ->label(__('sales::quote.actions.create_revision'))
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status->canCreateRevision())
                ->action(function ($record) {
                    $newQuote = app(QuoteService::class)->createRevision($record);

                    Notification::make()
                        ->success()
                        ->title(__('sales::quote.notifications.revision_created'))
                        ->body("Quote #{$newQuote->quote_number} (v{$newQuote->version}) created.")
                        ->send();

                    return redirect(QuoteResource::getUrl('view', ['record' => $newQuote]));
                }),

            Action::make('duplicate')
                ->label(__('sales::quote.actions.duplicate'))
                ->icon('heroicon-o-document-duplicate')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $newQuote = app(QuoteService::class)->duplicate($record);

                    Notification::make()
                        ->success()
                        ->title(__('sales::quote.notifications.duplicated'))
                        ->send();

                    return redirect(QuoteResource::getUrl('edit', ['record' => $newQuote]));
                }),

            Action::make('cancel')
                ->label(__('sales::quote.actions.cancel'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status->canBeCancelled())
                ->action(function ($record) {
                    app(QuoteService::class)->cancel($record);

                    Notification::make()
                        ->success()
                        ->title(__('sales::quote.notifications.cancelled'))
                        ->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }
}

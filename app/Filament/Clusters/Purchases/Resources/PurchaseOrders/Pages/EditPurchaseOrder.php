<?php

namespace App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages;

use App\Enums\Purchases\PurchaseOrderStatus;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Services\PurchaseOrderService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_rfq')
                ->label(__('purchase_orders.actions.send_rfq'))
                ->icon('heroicon-o-paper-airplane')
                ->color('blue')
                ->visible(fn() => $this->record->status === PurchaseOrderStatus::RFQ)
                ->requiresConfirmation()
                ->action(function () {
                    app(PurchaseOrderService::class)->sendRFQ($this->record, \Illuminate\Support\Facades\Auth::user());

                    Notification::make()
                        ->title(__('purchase_orders.notifications.rfq_sent'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Action::make('send')
                ->label(__('purchase_orders.actions.send'))
                ->icon('heroicon-o-paper-airplane')
                ->color('blue')
                ->visible(fn() => $this->record->status === PurchaseOrderStatus::Draft)
                ->requiresConfirmation()
                ->action(function () {
                    app(PurchaseOrderService::class)->send($this->record, \Illuminate\Support\Facades\Auth::user());

                    Notification::make()
                        ->title(__('purchase_orders.notifications.sent'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Action::make('confirm')
                ->label(__('purchase_orders.actions.confirm'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->record->canBeConfirmed())
                ->requiresConfirmation()
                ->action(function () {
                    app(PurchaseOrderService::class)->confirm($this->record, \Illuminate\Support\Facades\Auth::user());

                    Notification::make()
                        ->title(__('purchase_orders.notifications.confirmed'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Action::make('mark_done')
                ->label(__('purchase_orders.actions.mark_done'))
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn() => $this->record->status === PurchaseOrderStatus::FullyBilled)
                ->requiresConfirmation()
                ->action(function () {
                    app(PurchaseOrderService::class)->markAsDone($this->record, \Illuminate\Support\Facades\Auth::user());

                    Notification::make()
                        ->title(__('purchase_orders.notifications.marked_done'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Action::make('cancel')
                ->label(__('purchase_orders.actions.cancel'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn() => $this->record->canBeCancelled())
                ->requiresConfirmation()
                ->action(function () {
                    app(PurchaseOrderService::class)->cancel($this->record, \Illuminate\Support\Facades\Auth::user());

                    Notification::make()
                        ->title(__('purchase_orders.notifications.cancelled'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            DeleteAction::make()
                ->visible(fn() => in_array($this->record->status, [PurchaseOrderStatus::RFQ, PurchaseOrderStatus::Draft])),
        ];
    }
}

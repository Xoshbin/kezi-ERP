<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Kezi\Manufacturing\Enums\ManufacturingOrderStatus;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource;
use Kezi\Manufacturing\Services\ManufacturingOrderService;

class ViewManufacturingOrder extends ViewRecord
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->status === ManufacturingOrderStatus::Draft),

            Action::make('confirm')
                ->label(__('manufacturing::manufacturing.actions.confirm'))
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === ManufacturingOrderStatus::Draft)
                ->action(function () {
                    try {
                        app(ManufacturingOrderService::class)->confirm($this->record);

                        Notification::make()
                            ->success()
                            ->title(__('manufacturing::manufacturing.notifications.confirmed'))
                            ->body('The manufacturing order has been confirmed and is ready for production.')
                            ->send();

                        redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('manufacturing::manufacturing.notifications.error'))
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('start_production')
                ->label(__('manufacturing::manufacturing.actions.start_production'))
                ->icon('heroicon-o-play')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === ManufacturingOrderStatus::Confirmed)
                ->action(function () {
                    try {
                        app(ManufacturingOrderService::class)->startProduction($this->record);

                        Notification::make()
                            ->success()
                            ->title(__('manufacturing::manufacturing.notifications.started'))
                            ->body('Components have been consumed and production has started.')
                            ->send();

                        redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('manufacturing::manufacturing.notifications.error'))
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('complete')
                ->label(__('manufacturing::manufacturing.actions.complete_production'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === ManufacturingOrderStatus::InProgress)
                ->action(function () {
                    try {
                        app(ManufacturingOrderService::class)->complete($this->record);

                        Notification::make()
                            ->success()
                            ->title(__('manufacturing::manufacturing.notifications.completed'))
                            ->body('Finished goods have been added to inventory.')
                            ->send();

                        redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('manufacturing::manufacturing.notifications.error'))
                            ->body(implode("\n", $e->validator->errors()->all()))
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('manufacturing::manufacturing.notifications.error'))
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('cancel')
                ->label(__('manufacturing::manufacturing.actions.cancel'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status, [
                    ManufacturingOrderStatus::Draft,
                    ManufacturingOrderStatus::Confirmed,
                    ManufacturingOrderStatus::InProgress,
                ]))
                ->action(function () {
                    try {
                        app(ManufacturingOrderService::class)->cancel($this->record);

                        Notification::make()
                            ->success()
                            ->title(__('manufacturing::manufacturing.notifications.cancelled'))
                            ->send();

                        redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('manufacturing::manufacturing.notifications.error'))
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            DeleteAction::make()
                ->visible(fn () => $this->record->status === ManufacturingOrderStatus::Draft),
        ];
    }
}

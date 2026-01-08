<?php

namespace Modules\Manufacturing\Filament\Resources\ManufacturingOrderResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Filament\Resources\ManufacturingOrderResource;
use Modules\Manufacturing\Services\ManufacturingOrderService;

class ViewManufacturingOrder extends ViewRecord
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->status === ManufacturingOrderStatus::Draft),

            Action::make('confirm')
                ->label('Confirm')
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === ManufacturingOrderStatus::Draft)
                ->action(function () {
                    try {
                        app(ManufacturingOrderService::class)->confirm($this->record);

                        Notification::make()
                            ->success()
                            ->title('Manufacturing Order Confirmed')
                            ->body('The manufacturing order has been confirmed and is ready for production.')
                            ->send();

                        redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('start_production')
                ->label('Start Production')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === ManufacturingOrderStatus::Confirmed)
                ->action(function () {
                    try {
                        app(ManufacturingOrderService::class)->startProduction($this->record);

                        Notification::make()
                            ->success()
                            ->title('Production Started')
                            ->body('Components have been consumed and production has started.')
                            ->send();

                        redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('complete')
                ->label('Complete Production')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === ManufacturingOrderStatus::InProgress)
                ->action(function () {
                    try {
                        app(ManufacturingOrderService::class)->complete($this->record);

                        Notification::make()
                            ->success()
                            ->title('Production Completed')
                            ->body('Finished goods have been added to inventory.')
                            ->send();

                        redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status, [
                    ManufacturingOrderStatus::Draft,
                    ManufacturingOrderStatus::Confirmed,
                ]))
                ->action(function () {
                    try {
                        app(ManufacturingOrderService::class)->cancel($this->record);

                        Notification::make()
                            ->success()
                            ->title('Manufacturing Order Cancelled')
                            ->send();

                        redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            DeleteAction::make()
                ->visible(fn () => $this->record->status === ManufacturingOrderStatus::Draft),
        ];
    }
}

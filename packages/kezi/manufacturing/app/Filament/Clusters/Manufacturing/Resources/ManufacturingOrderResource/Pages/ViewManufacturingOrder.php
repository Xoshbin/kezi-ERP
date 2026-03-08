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

/**
 * @extends ViewRecord<\Kezi\Manufacturing\Models\ManufacturingOrder>
 */
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
                            ->body(__('manufacturing::exceptions.notifications.order_confirmed'))
                            ->send();

                        redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('manufacturing::exceptions.notifications.confirm_failed'))
                            ->body($e->getMessage())
                            ->persistent()
                            ->actions([
                                Action::make('edit_manufacturing_order')
                                    ->label(__('manufacturing::exceptions.actions.edit_order'))
                                    ->button()
                                    ->url(ManufacturingOrderResource::getUrl('edit', ['record' => $this->record])),
                            ])
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
                            ->body(__('manufacturing::exceptions.notifications.production_started'))
                            ->send();

                        redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('manufacturing::exceptions.notifications.start_failed'))
                            ->body($e->getMessage())
                            ->persistent()
                            ->actions([
                                Action::make('edit_manufacturing_order')
                                    ->label(__('manufacturing::exceptions.actions.edit_order'))
                                    ->button()
                                    ->url(ManufacturingOrderResource::getUrl('edit', ['record' => $this->record])),
                            ])
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
                            ->body(__('manufacturing::exceptions.notifications.production_completed'))
                            ->send();

                        redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('manufacturing::exceptions.notifications.complete_failed'))
                            ->body(implode("\n", $e->validator->errors()->all()))
                            ->persistent()
                            ->actions([
                                Action::make('edit_manufacturing_order')
                                    ->label(__('manufacturing::exceptions.actions.edit_order'))
                                    ->button()
                                    ->url(ManufacturingOrderResource::getUrl('edit', ['record' => $this->record])),
                            ])
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('manufacturing::exceptions.notifications.complete_failed'))
                            ->body($e->getMessage())
                            ->persistent()
                            ->actions([
                                Action::make('edit_manufacturing_order')
                                    ->label(__('manufacturing::exceptions.actions.edit_order'))
                                    ->button()
                                    ->url(ManufacturingOrderResource::getUrl('edit', ['record' => $this->record])),
                            ])
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
                            ->title(__('manufacturing::exceptions.notifications.cancel_failed'))
                            ->body($e->getMessage())
                            ->persistent()
                            ->actions([
                                Action::make('view_manufacturing_order')
                                    ->label(__('manufacturing::exceptions.actions.view_order'))
                                    ->button()
                                    ->url(ManufacturingOrderResource::getUrl('view', ['record' => $this->record])),
                            ])
                            ->send();
                    }
                }),

            DeleteAction::make()
                ->visible(fn () => $this->record->status === ManufacturingOrderStatus::Draft),
        ];
    }
}

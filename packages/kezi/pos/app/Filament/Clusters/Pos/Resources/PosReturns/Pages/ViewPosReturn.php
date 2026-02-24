<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosReturns\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Kezi\Pos\Actions\ApprovePosReturnAction;
use Kezi\Pos\Actions\ProcessPosReturnAction;
use Kezi\Pos\Actions\RejectPosReturnAction;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosReturns\PosReturnResource;
use Kezi\Pos\Models\PosReturn;

class ViewPosReturn extends ViewRecord
{
    protected static string $resource = PosReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('pos::pos_return.action.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (PosReturn $record): bool => $record->canBeApproved())
                ->action(function (PosReturn $record): void {
                    app(ApprovePosReturnAction::class)->execute($record, auth()->user());
                    $this->record = $this->record->fresh();
                    Notification::make()
                        ->success()
                        ->title(__('pos::pos_return.notification.approved'))
                        ->send();
                }),

            Action::make('reject')
                ->label(__('pos::pos_return.action.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (PosReturn $record): bool => $record->canBeApproved())
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label(__('pos::pos_return.action.reject_reason'))
                        ->required()
                        ->rows(3),
                ])
                ->action(function (PosReturn $record, array $data): void {
                    app(RejectPosReturnAction::class)->execute($record, auth()->user(), $data['reason']);
                    $this->record = $this->record->fresh();
                    Notification::make()
                        ->warning()
                        ->title(__('pos::pos_return.notification.rejected'))
                        ->send();
                }),

            Action::make('process')
                ->label(__('pos::pos_return.action.process'))
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (PosReturn $record): bool => $record->status === PosReturnStatus::Approved)
                ->action(function (PosReturn $record): void {
                    try {
                        app(ProcessPosReturnAction::class)->execute($record, auth()->user());
                        $this->record = $this->record->fresh();
                        Notification::make()
                            ->success()
                            ->title(__('pos::pos_return.notification.processed'))
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('pos::pos_return.notification.process_failed'))
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}

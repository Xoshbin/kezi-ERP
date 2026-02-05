<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions;

use App\Models\User;
use \Filament\Actions\Action;
use Filament\Notifications\Notification;
use Kezi\Inventory\DataTransferObjects\Inventory\ReceiveTransferDTO;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Inventory\Services\Inventory\TransferOrderService;

/**
 * Receive Transfer Action
 *
 * Filament action to receive an internal transfer (move stock from transit to destination).
 */
class ReceiveTransferAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'receive';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('inventory::transfer.actions.receive'))
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('inventory::transfer.actions.receive_heading'))
            ->modalDescription(__('inventory::transfer.actions.receive_description'))
            ->visible(fn (StockPicking $record): bool => $record->canBeReceived())
            ->action(function (StockPicking $record): void {
                /** @var User $user */
                $user = auth()->user();

                $dto = new ReceiveTransferDTO(
                    stock_picking_id: $record->id,
                    received_by_user_id: $user->id,
                );

                app(TransferOrderService::class)->receive($record, $dto, $user);

                Notification::make()
                    ->title(__('inventory::transfer.notifications.received'))
                    ->success()
                    ->send();
            });
    }
}

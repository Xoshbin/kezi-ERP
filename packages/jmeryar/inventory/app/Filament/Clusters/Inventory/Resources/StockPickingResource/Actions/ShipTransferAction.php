<?php

namespace Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Jmeryar\Inventory\DataTransferObjects\Inventory\ShipTransferDTO;
use Jmeryar\Inventory\Models\StockPicking;
use Jmeryar\Inventory\Services\Inventory\TransferOrderService;

/**
 * Ship Transfer Action
 *
 * Filament action to ship an internal transfer (move stock to transit location).
 */
class ShipTransferAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'ship';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('inventory::transfer.actions.ship'))
            ->icon('heroicon-o-truck')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(__('inventory::transfer.actions.ship_heading'))
            ->modalDescription(__('inventory::transfer.actions.ship_description'))
            ->visible(fn (StockPicking $record): bool => $record->canBeShipped())
            ->action(function (StockPicking $record): void {
                /** @var User $user */
                $user = auth()->user();

                $dto = new ShipTransferDTO(
                    stock_picking_id: $record->id,
                    shipped_by_user_id: $user->id,
                );

                app(TransferOrderService::class)->ship($record, $dto, $user);

                Notification::make()
                    ->title(__('inventory::transfer.notifications.shipped'))
                    ->success()
                    ->send();
            });
    }
}

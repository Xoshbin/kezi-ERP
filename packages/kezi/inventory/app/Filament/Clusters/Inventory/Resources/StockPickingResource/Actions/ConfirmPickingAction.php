<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions;

use DB;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Events\Inventory\StockMoveConfirmed;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource;
use Kezi\Inventory\Models\StockPicking;

class ConfirmPickingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'confirm';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('inventory::stock_picking.states.confirmed'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('inventory::stock_picking.notifications.confirmed'))
            ->modalDescription(__('inventory::stock_picking.notifications.confirm_description'))
            ->modalSubmitActionLabel(__('inventory::stock_picking.states.confirmed'))
            ->action(function (Model $record) {
                /** @var StockPicking $record */
                $this->confirmPicking($record);
            });
    }

    protected function confirmPicking(StockPicking $picking): void
    {
        try {
            DB::transaction(function () use ($picking) {
                // Update picking state
                $picking->update([
                    'state' => StockPickingState::Confirmed,
                ]);

                // Confirm all associated stock moves
                $picking->stockMoves()->update([
                    'status' => StockMoveStatus::Confirmed,
                ]);

                // Dispatch events for confirmed moves to update delivered quantities
                foreach ($picking->stockMoves as $move) {
                    event(new StockMoveConfirmed($move));
                }
            });

            Notification::make()
                ->title(__('inventory::stock_picking.notifications.confirmed'))
                ->body(__('inventory::stock_picking.notifications.confirmed_body'))
                ->success()
                ->send();

            // Refresh the page to show updated state
            $this->getLivewire()->redirect(StockPickingResource::getUrl('view', ['record' => $picking]));
        } catch (Exception $e) {
            Notification::make()
                ->title(__('inventory::stock_picking.notifications.error'))
                ->body(__('inventory::stock_picking.notifications.failed_to_confirm', ['error' => $e->getMessage()]))
                ->persistent()
                ->danger()
                ->send();
        }
    }
}

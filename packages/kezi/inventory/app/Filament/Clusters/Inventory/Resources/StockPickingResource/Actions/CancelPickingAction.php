<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions;

use DB;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Inventory\Services\Inventory\StockReservationService;

class CancelPickingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('inventory::stock_picking.states.cancelled'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('inventory::stock_picking.notifications.cancelled'))
            ->modalDescription(__('inventory::stock_picking.notifications.cancel_description'))
            ->modalSubmitActionLabel(__('inventory::stock_picking.states.cancelled'))
            ->action(function (Model $record) {
                /** @var StockPicking $record */
                $this->cancelPicking($record);
            });
    }

    protected function cancelPicking(StockPicking $picking): void
    {
        try {
            DB::transaction(function () use ($picking) {
                $reservationService = app(StockReservationService::class);

                // Release reservations for all moves
                foreach ($picking->stockMoves as $move) {
                    // Release any reservations
                    $reservationService->releaseForMove($move);

                    // Cancel the move
                    $move->update([
                        'status' => StockMoveStatus::Cancelled,
                    ]);

                    // Delete any lot lines
                    $move->stockMoveLines()->delete();
                }

                // Update picking state
                $picking->update([
                    'state' => StockPickingState::Cancelled,
                ]);
            });

            Notification::make()
                ->title(__('inventory::stock_picking.notifications.cancelled'))
                ->body(__('inventory::stock_picking.notifications.cancelled_body'))
                ->success()
                ->send();

            // Refresh the page to show updated state
            $this->getLivewire()->redirect(request()->url());
        } catch (Exception $e) {
            Notification::make()
                ->title(__('inventory::stock_picking.notifications.error'))
                ->body(__('inventory::stock_picking.notifications.failed_to_cancel', ['error' => $e->getMessage()]))
                ->persistent()
                ->danger()
                ->send();
        }
    }
}

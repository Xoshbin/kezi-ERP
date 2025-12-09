<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions;


use DB;

use Exception;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

use Modules\Inventory\Models\StockPicking;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Services\Inventory\StockReservationService;

class CancelPickingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Cancel'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('Cancel Picking'))
            ->modalDescription(__('Are you sure you want to cancel this picking? This will cancel all associated stock moves and release any reservations.'))
            ->modalSubmitActionLabel(__('Cancel'))
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
                ->title(__('Picking Cancelled'))
                ->body(__('The picking has been cancelled successfully. All reservations have been released.'))
                ->success()
                ->send();

            // Refresh the page to show updated state
            $this->getLivewire()->redirect(request()->url());
        } catch (Exception $e) {
            Notification::make()
                ->title(__('Error'))
                ->body(__('Failed to cancel picking: :error', ['error' => $e->getMessage()]))
                ->danger()
                ->send();
        }
    }
}

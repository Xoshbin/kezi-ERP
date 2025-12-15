<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions;

use DB;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Events\Inventory\StockMoveConfirmed;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource;
use Modules\Inventory\Models\StockPicking;

class ConfirmPickingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'confirm';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Confirm'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('Confirm Picking'))
            ->modalDescription(__('Are you sure you want to confirm this picking? This will confirm all associated stock moves.'))
            ->modalSubmitActionLabel(__('Confirm'))
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
                ->title(__('Picking Confirmed'))
                ->body(__('The picking has been confirmed successfully. All stock moves are now confirmed.'))
                ->success()
                ->send();

            // Refresh the page to show updated state
            $this->getLivewire()->redirect(StockPickingResource::getUrl('view', ['record' => $picking]));
        } catch (Exception $e) {
            Notification::make()
                ->title(__('Error'))
                ->body(__('Failed to confirm picking: :error', ['error' => $e->getMessage()]))
                ->danger()
                ->send();
        }
    }
}

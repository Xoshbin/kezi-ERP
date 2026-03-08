<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Kezi\Inventory\Actions\Inventory\ConfirmStockMoveAction as ConfirmStockMoveActionClass;
use Kezi\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Exceptions\Inventory\InsufficientCostInformationException;
use Kezi\Inventory\Models\StockMove;

class ConfirmStockMoveAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'confirm';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('inventory::stock_move.actions.confirm'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('inventory::stock_move.actions.confirm_modal_heading'))
            ->modalDescription(__('inventory::stock_move.actions.confirm_modal_description'))
            ->modalSubmitActionLabel(__('inventory::stock_move.actions.confirm'))
            ->visible(
                fn (Model $record): bool => $record instanceof StockMove && $record->status === StockMoveStatus::Draft
            )
            ->action(function (Model $record) {
                /** @var StockMove $record */
                $this->confirmStockMove($record);
            });
    }

    protected function confirmStockMove(StockMove $stockMove): void
    {
        try {
            $dto = new ConfirmStockMoveDTO(
                stock_move_id: $stockMove->id
            );

            app(ConfirmStockMoveActionClass::class)->execute($dto);

            Notification::make()
                ->title(__('inventory::stock_move.notifications.confirmed'))
                ->body(__('inventory::stock_move.notifications.confirmed_body'))
                ->success()
                ->send();

            // Refresh the page to show updated state
            $this->getLivewire()->redirect(request()->url());
        } catch (InsufficientCostInformationException $e) {
            $this->handleCostInformationError($e);
        } catch (Exception $e) {
            $this->handleGenericError($e);
        }
    }

    protected function handleCostInformationError(InsufficientCostInformationException $exception): void
    {
        $errorData = $exception->getUserFriendlyErrorData();

        // Show user-friendly notification with detailed information
        Notification::make()
            ->title($errorData['title'])
            ->body($exception->getUserFriendlyMessage()."\n\n".$errorData['explanation'])
            ->danger()
            ->persistent()
            ->actions([
                Action::make('create_vendor_bill')
                    ->label(__('inventory::exceptions.cost_validation_errors.notifications.create_vendor_bill'))
                    ->button()
                    ->url(route('filament.kezi.accounting.resources.vendor-bills.create', ['tenant' => Filament::getTenant()]))
                    ->openUrlInNewTab(),
            ])
            ->send();
    }

    protected function handleGenericError(Exception $exception): void
    {
        Notification::make()
            ->title(__('inventory::exceptions.generic_error.title'))
            ->body($exception->getMessage() ?: __('inventory::exceptions.generic_error.body'))
            ->persistent()
            ->danger()
            ->send();

        // Log the error for debugging
        Log::error('Stock move confirmation failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

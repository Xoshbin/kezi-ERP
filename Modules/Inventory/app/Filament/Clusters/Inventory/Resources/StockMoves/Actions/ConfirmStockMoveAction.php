<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Actions;

use App\Actions\Inventory\ConfirmStockMoveAction as ConfirmStockMoveActionClass;
use App\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Exceptions\Inventory\InsufficientCostInformationException;
use App\Models\StockMove;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Log;

class ConfirmStockMoveAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'confirm';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Confirm Movement'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('Confirm Stock Movement'))
            ->modalDescription(__('Are you sure you want to confirm this stock movement? This will process the inventory changes and cannot be undone.'))
            ->modalSubmitActionLabel(__('Confirm Movement'))
            ->visible(
                fn(Model $record): bool =>
                $record instanceof StockMove && $record->status === StockMoveStatus::Draft
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
                ->title(__('Movement Confirmed'))
                ->body(__('The stock movement has been confirmed successfully. Inventory quantities have been updated.'))
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
            ->body($exception->getUserFriendlyMessage() . "\n\n" . $errorData['explanation'])
            ->danger()
            ->persistent()
            ->actions([
                Action::make('create_vendor_bill')
                    ->label(__('Create Vendor Bill'))
                    ->button()
                    ->url(route('filament.jmeryar.accounting.resources.vendor-bills.create', ['tenant' => Filament::getTenant()]))
                    ->openUrlInNewTab(),
            ])
            ->send();
    }

    protected function handleGenericError(Exception $exception): void
    {
        Notification::make()
            ->title(__('Error Confirming Movement'))
            ->body(__('An unexpected error occurred while confirming the stock movement. Please try again or contact support.'))
            ->danger()
            ->send();

        // Log the error for debugging
        Log::error('Stock move confirmation failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Pages;

use Carbon\Carbon;
use \Filament\Actions\Action;
use \Filament\Actions\DeleteAction;
use \Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Inventory\Actions\Inventory\UpdateStockMoveWithProductLinesAction;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\UpdateStockMoveWithProductLinesDTO;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Exceptions\Inventory\InsufficientCostInformationException;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\StockMoveResource;
use Kezi\Inventory\Models\StockMove;

/**
 * @extends EditRecord<\Kezi\Inventory\Models\StockMove>
 */
class EditStockMove extends EditRecord
{
    protected static string $resource = StockMoveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon('heroicon-o-eye'),
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn (): bool => ($this->getRecord() instanceof StockMove) && $this->getRecord()->status === StockMoveStatus::Draft),
            DocsAction::make('stock-movements'),
        ];
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $reference = $record->reference ?? $record->id ?? '';

        return __('inventory::stock_move.edit_title', ['reference' => $reference]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if (! $record instanceof StockMove) {
            return $data;
        }

        $record->loadMissing('productLines');

        $productLinesData = $record->productLines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'quantity' => $line->quantity,
                'from_location_id' => $line->from_location_id,
                'to_location_id' => $line->to_location_id,
                'description' => $line->description,
                'source_type' => $line->source_type,
                'source_id' => $line->source_id,
            ];
        })->toArray();

        $data['productLines'] = $productLinesData;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Convert product lines data to DTOs
        $productLineDTOs = [];
        foreach ($data['productLines'] ?? [] as $lineData) {
            $productLineDTOs[] = new CreateStockMoveProductLineDTO(
                product_id: $lineData['product_id'],
                quantity: $lineData['quantity'],
                from_location_id: $lineData['from_location_id'],
                to_location_id: $lineData['to_location_id'],
                description: $lineData['description'] ?? null,
                source_type: $lineData['source_type'] ?? null,
                source_id: isset($lineData['source_id']) ? (int) $lineData['source_id'] : null,
            );
        }

        $dto = new UpdateStockMoveWithProductLinesDTO(
            id: (int) $record->getKey(),
            move_type: StockMoveType::from($data['move_type']),
            status: StockMoveStatus::from($data['status']),
            move_date: Carbon::parse($data['move_date']),
            product_lines: $productLineDTOs,
            reference: $data['reference'] ?? null,
            description: $data['description'] ?? null,
            source_type: $data['source_type'] ?? null,
            source_id: isset($data['source_id']) ? (int) $data['source_id'] : null,
        );

        try {
            return app(UpdateStockMoveWithProductLinesAction::class)->execute($dto);
        } catch (InsufficientCostInformationException $e) {
            // Show user-friendly error notification
            Notification::make()
                ->title(__('inventory::inventory_accounting.cost_validation_errors.title'))
                ->body($e->getUserFriendlyMessage())
                ->danger()
                ->persistent()
                ->actions([
                    Action::make('create_vendor_bill')
                        ->label(__('Create Vendor Bill'))
                        ->button()
                        ->url(route('filament.kezi.accounting.resources.vendor-bills.create', ['tenant' => Filament::getTenant()]))
                        ->openUrlInNewTab(),
                ])
                ->send();

            // Halt the update process
            $this->halt();

            // This line will never be reached due to halt(), but satisfies the return type
            throw $e;
        }
    }
}

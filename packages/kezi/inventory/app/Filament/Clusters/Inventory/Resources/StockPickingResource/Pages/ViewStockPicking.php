<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages;

use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions\AssignPickingAction;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions\CancelPickingAction;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions\ConfirmPickingAction;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions\ReceiveTransferAction;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions\ShipTransferAction;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions\ValidatePickingAction;
use Kezi\Inventory\Models\StockPicking;

class ViewStockPicking extends ViewRecord
{
    protected static string $resource = StockPickingResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('inventory::stock_picking.label'))
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('reference')
                                ->label(__('inventory::stock_picking.reference')),

                            TextEntry::make('type')
                                ->label(__('inventory::stock_picking.types.receipt'))
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'receipt' => 'success',
                                    'delivery' => 'danger',
                                    'internal' => 'info',
                                    default => 'gray',
                                }),

                            TextEntry::make('state')
                                ->label(__('inventory::stock_picking.states.draft'))
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'draft' => 'gray',
                                    'confirmed' => 'warning',
                                    'assigned' => 'info',
                                    'shipped' => 'warning',
                                    'done' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('partner.name')
                                ->label(__('inventory::stock_picking.partner'))
                                ->placeholder('—'),

                            TextEntry::make('scheduled_date')
                                ->label(__('inventory::stock_picking.scheduled_date'))
                                ->dateTime(),

                            TextEntry::make('completed_at')
                                ->label(__('inventory::stock_picking.completed_at'))
                                ->dateTime()
                                ->placeholder('—'),

                            TextEntry::make('origin')
                                ->label(__('inventory::stock_picking.origin'))
                                ->placeholder('—'),

                            TextEntry::make('stockMoves')
                                ->label(__('inventory::stock_picking.total_moves'))
                                ->getStateUsing(fn (StockPicking $record) => $record->stockMoves()->count()),

                            TextEntry::make('created_at')
                                ->label(__('common.created_at'))
                                ->dateTime(),
                        ]),
                ]),

            Section::make(__('inventory::stock_picking.stock_moves'))
                ->schema([
                    TextEntry::make('stockMoves')
                        ->label(__('inventory::stock_picking.move_details'))
                        ->listWithLineBreaks()
                        ->bulleted()
                        ->getStateUsing(function (StockPicking $record) {
                            return $record->stockMoves->flatMap(function ($move) {
                                return $move->productLines->map(function ($productLine) use ($move) {
                                    $lotInfo = $move->stockMoveLines
                                        ->where('stock_move_product_line_id', $productLine->id)
                                        ->map(function ($line) {
                                            $lotCode = $line->lot?->lot_code;
                                            $qty = number_format($line->quantity, 2);

                                            return $lotCode ? "Lot: {$lotCode} (Qty: {$qty})" : "Qty: {$qty}";
                                        })->join(', ');

                                    $lotDisplay = $lotInfo ? " - {$lotInfo}" : '';

                                    return "{$productLine->product->name}: ".number_format($productLine->quantity, 2).
                                        " from {$productLine->fromLocation?->name} to {$productLine->toLocation?->name}".
                                        " (Status: {$move->status->label()}){$lotDisplay}";
                                });
                            })->toArray();
                        }),
                ])
                ->visible(fn (StockPicking $record) => $record->stockMoves->isNotEmpty()),
        ]);
    }

    protected function getHeaderActions(): array
    {
        /** @var StockPicking $record */
        $record = $this->record;

        $actions = [
            EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->visible(fn () => $record->state === StockPickingState::Draft),
        ];

        // Add state-specific actions
        switch ($record->state) {
            case StockPickingState::Draft:
                $actions[] = ConfirmPickingAction::make();
                $actions[] = CancelPickingAction::make();
                break;

            case StockPickingState::Confirmed:
                // For internal transfers, show Ship action
                if ($record->isInternalTransfer()) {
                    $actions[] = ShipTransferAction::make();
                } else {
                    $actions[] = AssignPickingAction::make();
                }
                $actions[] = CancelPickingAction::make();
                break;

            case StockPickingState::Assigned:
                // For internal transfers, show Ship action
                if ($record->isInternalTransfer()) {
                    $actions[] = ShipTransferAction::make();
                } else {
                    $actions[] = ValidatePickingAction::make();
                }
                $actions[] = CancelPickingAction::make();
                break;

            case StockPickingState::Shipped:
                // Only for internal transfers - show Receive action
                $actions[] = ReceiveTransferAction::make();
                break;
        }

        return $actions;
    }
}

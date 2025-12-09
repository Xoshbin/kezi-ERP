<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages;

use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions\AssignPickingAction;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions\CancelPickingAction;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions\ConfirmPickingAction;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions\CreateBackorderAction;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions\ValidatePickingAction;
use Modules\Inventory\Models\StockPicking;

class ViewStockPicking extends ViewRecord
{
    protected static string $resource = StockPickingResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Picking Information')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('reference')
                                ->label('Reference'),

                            TextEntry::make('type')
                                ->label('Type')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'receipt' => 'success',
                                    'delivery' => 'danger',
                                    'internal' => 'info',
                                    default => 'gray',
                                }),

                            TextEntry::make('state')
                                ->label('State')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'draft' => 'gray',
                                    'confirmed' => 'warning',
                                    'assigned' => 'info',
                                    'done' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('partner.name')
                                ->label('Partner')
                                ->placeholder('—'),

                            TextEntry::make('scheduled_date')
                                ->label('Scheduled Date')
                                ->dateTime(),

                            TextEntry::make('completed_at')
                                ->label('Completed At')
                                ->dateTime()
                                ->placeholder('—'),

                            TextEntry::make('origin')
                                ->label('Origin')
                                ->placeholder('—'),

                            TextEntry::make('stockMoves')
                                ->label('Total Moves')
                                ->getStateUsing(fn (StockPicking $record) => $record->stockMoves()->count()),

                            TextEntry::make('created_at')
                                ->label('Created At')
                                ->dateTime(),
                        ]),
                ]),

            Section::make('Stock Moves')
                ->schema([
                    TextEntry::make('stockMoves')
                        ->label('Move Details')
                        ->listWithLineBreaks()
                        ->bulleted()
                        ->getStateUsing(function (StockPicking $record) {
                            return $record->stockMoves->flatMap(function ($move) {
                                return $move->productLines->map(function ($productLine) use ($move) {
                                    $lotInfo = $move->stockMoveLines
                                        ->where('stock_move_product_line_id', $productLine->id)
                                        ->map(function ($line) {
                                            return "Lot: {$line->lot->lot_code} (Qty: ".number_format($line->quantity, 2).')';
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
                $actions[] = AssignPickingAction::make();
                $actions[] = CancelPickingAction::make();
                break;

            case StockPickingState::Assigned:
                $actions[] = ValidatePickingAction::make();
                $actions[] = CreateBackorderAction::make();
                $actions[] = CancelPickingAction::make();
                break;
        }

        return $actions;
    }
}

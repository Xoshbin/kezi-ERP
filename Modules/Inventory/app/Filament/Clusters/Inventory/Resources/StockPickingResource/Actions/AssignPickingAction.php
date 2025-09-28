<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions;

use Exception;
use Filament\Actions\Action;
use Modules\Inventory\Models\Lot;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Modules\Inventory\Models\StockPicking;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Services\Inventory\StockReservationService;

class AssignPickingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'assign';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Assign'))
            ->icon('heroicon-o-clipboard-document-check')
            ->color('info')
            ->modalHeading('Assign Picking')
            ->modalDescription('Reserve stock and assign specific lots for this picking.')
            ->modalSubmitActionLabel('Assign')
            ->schema($this->getAssignSchema())
            ->action(function (Model $record, array $data) {
                /** @var StockPicking $record */
                $this->assignPicking($record, $data);
            });
    }

    protected function getAssignSchema(): array
    {
        return [
            Section::make(__('Stock Moves'))
                ->description(__('Review and assign lots for each stock move in this picking.'))
                ->schema([
                    Repeater::make('moves')
                        ->label(__('Stock Moves'))
                        ->schema([
                            Placeholder::make('product_info')
                                ->label(__('product.label'))
                                ->content(function ($get, $state) {
                                    if (!$state || !isset($state['product_name'])) {
                                        return '—';
                                    }
                                    return $state['product_name'] . ' (' . number_format($state['quantity'], 2) . ' units)';
                                }),

                            Placeholder::make('locations')
                                ->label(__('Route'))
                                ->content(function ($get, $state) {
                                    if (!$state) {
                                        return '—';
                                    }
                                    return ($state['from_location'] ?? '—') . ' → ' . ($state['to_location'] ?? '—');
                                }),

                            Repeater::make('lot_lines')
                                ->label(__('Lot Assignments'))
                                ->schema([
                                    Select::make('lot_id')
                                        ->label(__('Lot'))
                                        ->options(function ($get) {
                                            $moveData = $get('../../');
                                            if (!$moveData || !isset($moveData['product_id'])) {
                                                return [];
                                            }

                                            // Get available lots for this product
                                            return Lot::where('product_id', $moveData['product_id'])
                                                ->where('active', true)
                                                ->pluck('lot_code', 'id')
                                                ->toArray();
                                        })
                                        ->searchable()
                                        ->required(),

                                    TextInput::make('quantity')
                                        ->label(__('Quantity'))
                                        ->numeric()
                                        ->required()
                                        ->minValue(0.01)
                                        ->step(0.01),
                                ])
                                ->addActionLabel(__('Add Lot'))
                                ->collapsible()
                                ->defaultItems(0),
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->default(function (Model $record) {
                            /** @var StockPicking $record */
                            return $record->stockMoves->map(function ($move) {
                                return [
                                    'move_id' => $move->id,
                                    'product_id' => $move->product_id,
                                    'product_name' => $move->product->name,
                                    'quantity' => $move->quantity,
                                    'from_location' => $move->fromLocation?->name,
                                    'to_location' => $move->toLocation?->name,
                                    'lot_lines' => [],
                                ];
                            })->toArray();
                        }),
                ]),
        ];
    }

    protected function assignPicking(StockPicking $picking, array $data): void
    {
        try {
            DB::transaction(function () use ($picking, $data) {
                $reservationService = app(StockReservationService::class);

                // Process each move
                foreach ($data['moves'] as $moveData) {
                    $move = $picking->stockMoves()->find($moveData['move_id']);
                    if (!$move) {
                        continue;
                    }

                    // Reserve stock for the move - use first product line's from_location_id
                    $firstProductLine = $move->productLines()->first();
                    if ($firstProductLine) {
                        $reservationService->reserveForMove($move, $firstProductLine->from_location_id);

                        // Create lot lines if specified
                        if (!empty($moveData['lot_lines'])) {
                            foreach ($moveData['lot_lines'] as $lotLineData) {
                                $move->stockMoveLines()->create([
                                    'company_id' => $move->company_id,
                                    'stock_move_product_line_id' => $firstProductLine->id,
                                    'lot_id' => $lotLineData['lot_id'],
                                    'quantity' => $lotLineData['quantity'],
                                ]);
                            }
                        }
                    }
                }

                // Update picking state
                $picking->update([
                    'state' => StockPickingState::Assigned,
                ]);
            });

            Notification::make()
                ->title(__('Picking Assigned'))
                ->body(__('The picking has been assigned successfully. Stock has been reserved and lots have been allocated.'))
                ->success()
                ->send();

            // Refresh the page to show updated state
            $this->getLivewire()->redirect(request()->url());
        } catch (Exception $e) {
            Notification::make()
                ->title(__('Error'))
                ->body(__('Failed to assign picking: :error', ['error' => $e->getMessage()]))
                ->danger()
                ->send();
        }
    }
}

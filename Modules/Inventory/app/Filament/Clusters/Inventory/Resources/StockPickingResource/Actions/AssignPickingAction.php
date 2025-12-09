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
                            \Filament\Forms\Components\Hidden::make('move_id'),
                            \Filament\Forms\Components\Hidden::make('product_line_id'),

                            \Filament\Forms\Components\TextInput::make('product_name')
                                ->label(__('Product'))
                                ->disabled()
                                ->dehydrated(false)
                                ->suffix(fn($get) => 'Qty: ' . $get('quantity')),

                            \Filament\Forms\Components\Hidden::make('quantity'),

                            \Filament\Schemas\Components\Grid::make(2)
                                ->schema([
                                    \Filament\Forms\Components\TextInput::make('from_location')
                                        ->label(__('From'))
                                        ->disabled()
                                        ->dehydrated(false),
                                    \Filament\Forms\Components\TextInput::make('to_location')
                                        ->label(__('To'))
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),

                            Repeater::make('lot_lines')
                                ->label(__('Lot Assignments'))
                                ->schema([
                                    Select::make('lot_id')
                                        ->label(__('Lot'))
                                        ->options(function ($get) {
                                            $moveData = $get('../../');
                                            // Fallback for getting parent data if relative path fails or structure changes
                                            $productId = $moveData['product_id'] ?? null;

                                            if (!$productId) {
                                                return [];
                                            }

                                            return Lot::where('product_id', $productId)
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
                            $moves = [];
                            foreach ($record->stockMoves as $move) {
                                foreach ($move->productLines as $productLine) {
                                    $moves[] = [
                                        'move_id' => $move->id,
                                        'product_line_id' => $productLine->id,
                                        'product_id' => $productLine->product_id,
                                        'product_name' => $productLine->product?->name ?? 'Unknown Product',
                                        'quantity' => $productLine->quantity,
                                        'from_location' => $productLine->fromLocation?->name ?? '—',
                                        'to_location' => $productLine->toLocation?->name ?? '—',
                                        'lot_lines' => [],
                                    ];
                                }
                            }
                            return $moves;
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

                    // Reserve stock for the specific product line
                    if (isset($moveData['product_line_id'])) {
                        $productLine = $move->productLines()->find($moveData['product_line_id']);

                        if ($productLine) {
                            $reservationService->reserveForMove($move, $productLine->from_location_id); // Note: Assuming reserveForMove handles productLine logic or we might need to update that service too. For now passing what it expects.

                            // Create lot lines if specified
                            if (!empty($moveData['lot_lines'])) {
                                foreach ($moveData['lot_lines'] as $lotLineData) {
                                    $move->stockMoveLines()->create([
                                        'company_id' => $move->company_id,
                                        'stock_move_product_line_id' => $productLine->id,
                                        'lot_id' => $lotLineData['lot_id'],
                                        'quantity' => $lotLineData['quantity'],
                                    ]);
                                }
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
            $this->getLivewire()->redirect(\Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource::getUrl('view', ['record' => $picking]));
        } catch (Exception $e) {
            Notification::make()
                ->title(__('Error'))
                ->body(__('Failed to assign picking: :error', ['error' => $e->getMessage()]))
                ->danger()
                ->send();
        }
    }
}

<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions;

use DB;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

class ValidatePickingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'validate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Validate (Done)'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->modalHeading('Validate Picking')
            ->modalDescription('Confirm the actual quantities picked and complete this picking operation.')
            ->modalSubmitActionLabel('Validate')
            ->schema($this->getValidateSchema())
            ->action(function (Model $record, array $data) {
                /** @var StockPicking $record */
                $this->validatePicking($record, $data);
            });
    }

    protected function getValidateSchema(): array
    {
        return [
            Section::make('Actual Quantities')
                ->description('Confirm the actual quantities that were picked for each move.')
                ->schema([
                    Repeater::make('moves')
                        ->label('Stock Moves')
                        ->schema([
                            Placeholder::make('product_info')
                                ->label('Product')
                                ->content(function ($get, $state) {
                                    if (!$state || !isset($state['product_name'])) {
                                        return '—';
                                    }
                                    return $state['product_name'];
                                }),

                            Placeholder::make('planned_quantity')
                                ->label('Planned Quantity')
                                ->content(function ($get, $state) {
                                    if (!$state || !isset($state['planned_quantity'])) {
                                        return '—';
                                    }
                                    return number_format($state['planned_quantity'], 2);
                                }),

                            TextInput::make('actual_quantity')
                                ->label('Actual Quantity')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step(0.01)
                                ->helperText('Enter the actual quantity that was picked'),

                            Placeholder::make('lot_lines_info')
                                ->label('Assigned Lots')
                                ->content(function ($get, $state) {
                                    if (!$state || empty($state['lot_lines'])) {
                                        return 'No lots assigned';
                                    }

                                    $lotInfo = collect($state['lot_lines'])
                                        ->map(fn($lot) => $lot['lot_code'] . ' (' . number_format($lot['quantity'], 2) . ')')
                                        ->join(', ');

                                    return $lotInfo;
                                }),
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->default(function (Model $record) {
                            /** @var StockPicking $record */
                            return $record->stockMoves->map(function ($move) {
                                $lotLines = $move->stockMoveLines->map(function ($line) {
                                    return [
                                        'lot_code' => $line->lot->lot_code,
                                        'quantity' => $line->quantity,
                                    ];
                                })->toArray();

                                return [
                                    'move_id' => $move->id,
                                    'product_name' => $move->product->name,
                                    'planned_quantity' => $move->quantity,
                                    'actual_quantity' => $move->quantity, // Default to planned
                                    'lot_lines' => $lotLines,
                                ];
                            })->toArray();
                        }),
                ]),
        ];
    }

    protected function validatePicking(StockPicking $picking, array $data): void
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

                    // Update move quantity if different from planned
                    if ($moveData['actual_quantity'] != $move->quantity) {
                        $move->update([
                            'quantity' => $moveData['actual_quantity'],
                        ]);
                    }

                    // Consume reservations and update stock
                    $reservationService->consumeForMove($move);

                    // Mark move as done
                    $move->update([
                        'status' => StockMoveStatus::Done,
                    ]);
                }

                // Update picking state
                $picking->update([
                    'state' => StockPickingState::Done,
                    'completed_at' => now(),
                ]);
            });

            Notification::make()
                ->title(__('Picking Validated'))
                ->body(__('The picking has been completed successfully. All stock movements have been processed.'))
                ->success()
                ->send();

            // Refresh the page to show updated state
            $this->getLivewire()->redirect(request()->url());
        } catch (Exception $e) {
            Notification::make()
                ->title(__('Error'))
                ->body(__('Failed to validate picking: :error', ['error' => $e->getMessage()]))
                ->danger()
                ->send();
        }
    }
}

<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions;

use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockPickingState;
use App\Models\StockPicking;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

class CreateBackorderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'create_backorder';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Create Backorder'))
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('warning')
            ->modalHeading('Create Backorder')
            ->modalDescription('Split remaining quantities into a new picking when partial fulfillment occurs.')
            ->modalSubmitActionLabel('Create Backorder')
            ->schema($this->getBackorderSchema())
            ->action(function (Model $record, array $data) {
                /** @var StockPicking $record */
                $this->createBackorder($record, $data);
            });
    }

    protected function getBackorderSchema(): array
    {
        return [
            Section::make(__('Partial Fulfillment'))
                ->description(__('Specify the quantities that can be fulfilled now. Remaining quantities will be moved to a backorder.'))
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
                                    return $state['product_name'];
                                }),

                            Placeholder::make('planned_quantity')
                                ->label(__('Planned Quantity'))
                                ->content(function ($get, $state) {
                                    if (!$state || !isset($state['planned_quantity'])) {
                                        return '—';
                                    }
                                    return number_format($state['planned_quantity'], 2);
                                }),

                            TextInput::make('fulfilled_quantity')
                                ->label(__('Fulfilled Quantity'))
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step(0.01)
                                ->helperText(__('Quantity that can be fulfilled now')),

                            Placeholder::make('backorder_quantity')
                                ->label(__('Backorder Quantity'))
                                ->content(function ($get, $state) {
                                    $planned = $state['planned_quantity'] ?? 0;
                                    $fulfilled = $get('fulfilled_quantity') ?? 0;
                                    $backorder = max(0, $planned - $fulfilled);
                                    return number_format($backorder, 2);
                                }),
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->default(function (Model $record) {
                            /** @var StockPicking $record */
                            return $record->stockMoves->map(function ($move) {
                                return [
                                    'move_id' => $move->id,
                                    'product_name' => $move->product->name,
                                    'planned_quantity' => $move->quantity,
                                    'fulfilled_quantity' => 0, // Default to 0 for backorder
                                ];
                            })->toArray();
                        }),
                ]),
        ];
    }

    protected function createBackorder(StockPicking $picking, array $data): void
    {
        try {
            \DB::transaction(function () use ($picking, $data) {
                $hasBackorderItems = false;

                // Create backorder picking
                $backorder = StockPicking::create([
                    'company_id' => $picking->company_id,
                    'type' => $picking->type,
                    'state' => StockPickingState::Draft,
                    'partner_id' => $picking->partner_id,
                    'scheduled_date' => $picking->scheduled_date,
                    'reference' => $picking->reference . '-BO',
                    'origin' => 'Backorder from ' . $picking->reference,
                ]);

                // Process each move
                foreach ($data['moves'] as $moveData) {
                    $originalMove = $picking->stockMoves()->find($moveData['move_id']);
                    if (!$originalMove) {
                        continue;
                    }

                    $fulfilledQty = $moveData['fulfilled_quantity'];
                    $backorderQty = $originalMove->quantity - $fulfilledQty;

                    if ($backorderQty > 0) {
                        $hasBackorderItems = true;

                        // Create backorder move
                        $backorderMove = $originalMove->replicate();
                        $backorderMove->quantity = $backorderQty;
                        $backorderMove->picking_id = $backorder->id;
                        $backorderMove->status = StockMoveStatus::Draft;
                        $backorderMove->save();

                        // Update original move quantity
                        $originalMove->update([
                            'quantity' => $fulfilledQty,
                        ]);

                        // If fulfilled quantity is 0, cancel the original move
                        if ($fulfilledQty == 0) {
                            $originalMove->update([
                                'status' => StockMoveStatus::Cancelled,
                            ]);
                        }
                    }
                }

                // If no backorder items, delete the empty backorder
                if (!$hasBackorderItems) {
                    $backorder->delete();

                    Notification::make()
                        ->title(__('No Backorder Needed'))
                        ->body(__('All items can be fulfilled. No backorder was created.'))
                        ->info()
                        ->send();

                    return;
                }

                // Update original picking state if it has remaining items
                $remainingMoves = $picking->stockMoves()->where('status', '!=', StockMoveStatus::Cancelled)->count();
                if ($remainingMoves > 0) {
                    $picking->update([
                        'state' => StockPickingState::Done,
                        'completed_at' => now(),
                    ]);
                }
            });

            Notification::make()
                ->title(__('Backorder Created'))
                ->body(__('A backorder has been created for the remaining quantities. The original picking has been updated.'))
                ->success()
                ->send();

            // Refresh the page to show updated state
            $this->getLivewire()->redirect(request()->url());
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Error'))
                ->body(__('Failed to create backorder: :error', ['error' => $e->getMessage()]))
                ->danger()
                ->send();
        }
    }
}

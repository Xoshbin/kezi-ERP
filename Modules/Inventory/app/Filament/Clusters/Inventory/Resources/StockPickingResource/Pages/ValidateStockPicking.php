<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource;
use Modules\Inventory\Models\StockPicking;
use Modules\Inventory\Services\Inventory\StockReservationService;

class ValidateStockPicking extends Page
{
    use InteractsWithForms;

    protected static string $resource = StockPickingResource::class;

    protected string $view = 'inventory::filament.pages.validate-stock-picking';

    public StockPicking $record;

    public ?array $data = [];

    public function mount(StockPicking $record): void
    {
        $this->record = $record;

        $rows = [];
        foreach ($record->stockMoves as $move) {
            foreach ($move->productLines as $line) {
                // Find any existing Lot Lines for this move/product line combination
                // The relationship structure is elaborate: StockMove -> StockMoveLine (which has lot_id) -> StockMoveProductLine
                // Wait, StockMoveLine links to StockMoveProductLine via `stock_move_product_line_id`?
                // Or StockMoveLine IS the lot allocation?
                // Let's check the schema or relationships.
                // Based on ViewStockPicking:
                /*
                 $lotInfo = $move->stockMoveLines
                    ->where('stock_move_product_line_id', $productLine->id)
                    ...
                */
                // So we can fetch lot info.

                $lotLines = $move->stockMoveLines
                    ->where('stock_move_product_line_id', $line->id)
                    ->map(fn ($l) => [
                        'lot_code' => $l->lot?->lot_code ?? 'Unknown',
                        'quantity' => $l->quantity,
                    ])->values()->toArray();

                $rows[] = [
                    'move_id' => $move->id,
                    'product_line_id' => $line->id,
                    'product_name' => $line->product?->name ?? 'Unknown Product',
                    'planned_quantity' => $line->quantity,
                    'actual_quantity' => $line->quantity, // Default to planned quantity
                    'lot_lines' => $lotLines,
                ];
            }
        }

        $this->form->fill(['moves' => $rows]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Actual Quantities')
                    ->description('Confirm the actual quantities that were picked for each move.')
                    ->schema([
                        Repeater::make('moves')
                            ->label('Stock Moves')
                            ->schema([
                                TextInput::make('product_info')
                                    ->label('Product')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(fn ($state) => $state['product_name'] ?? '—'),

                                TextInput::make('planned_quantity')
                                    ->label('Planned Quantity')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(fn ($state) => number_format($state['planned_quantity'] ?? 0, 2)),

                                TextInput::make('actual_quantity')
                                    ->label('Actual / Fulfilled Quantity')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live() // Update backorder calcs immediately if we added that field
                                    ->default(fn ($state) => $state['actual_quantity'] ?? 0),

                                TextInput::make('lot_lines_info')
                                    ->label('Assigned Lots')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(function ($state) {
                                        if (! isset($state['lot_lines']) || empty($state['lot_lines'])) {
                                            return 'No lots assigned';
                                        }

                                        return collect($state['lot_lines'])
                                            ->map(fn ($lot) => $lot['lot_code'].' ('.number_format($lot['quantity'], 2).')')
                                            ->join(', ');
                                    }),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(4),
                    ]),
            ])
            ->statePath('data');
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('validate')
                ->label('Validate (Done)')
                ->color('success')
                ->icon('heroicon-o-check')
                ->icon('heroicon-o-check')
                ->action(function () {
                    // dd('Closure Running');
                    $this->validatePicking();
                }),

            Action::make('create_backorder')
                ->label('Create Backorder')
                ->color('warning')
                ->icon('heroicon-o-clock')
                ->action('createBackorder'),
        ];
    }

    public function validatePicking(): void
    {
        // dd('validatePicking called', $this->form->getState());
        \Illuminate\Support\Facades\Log::info('validatePicking called');
        $data = $this->form->getState();
        // dd('Data Retrieved', $data);
        if (empty($data['moves'])) {
            // dd('Moves Empty inside action');
        }
        $this->processValidation($data, false);
    }

    public function createBackorder(): void
    {
        $data = $this->form->getState();
        $this->processValidation($data, true);
    }

    protected function processValidation(array $data, bool $createBackorder): void
    {
        if (empty($data['moves'])) {
            Notification::make()->title('Error')->body('No lines to validate.')->danger()->send();

            return;
        }

        try {
            DB::transaction(function () use ($data, $createBackorder) {
                // Debug removed from here
                \Illuminate\Support\Facades\Log::info('Validation Start: '.json_encode($data));

                // 1. Prepare Backorder Data
                $backorderItems = [];
                foreach ($data['moves'] as $moveData) {
                    $planned = (float) ($moveData['planned_quantity'] ?? 0);
                    $actual = (float) ($moveData['actual_quantity'] ?? 0);

                    if ($actual < $planned) {
                        $backorderItems[] = [
                            'move_id' => $moveData['move_id'],
                            'product_line_id' => $moveData['product_line_id'],
                            'planned' => $planned,
                            'actual' => $actual,
                            'backorder_qty' => $planned - $actual,
                        ];
                    }
                }

                \Illuminate\Support\Facades\Log::info('Backorder Items: '.count($backorderItems));

                // 2. Create Backorder if requested AND needed
                if ($createBackorder && count($backorderItems) > 0) {
                    $this->createBackorderRecords($backorderItems);
                }

                // 3. Update Original Picking Lines to Actual
                $processedMoveIds = [];
                foreach ($data['moves'] as $moveData) {
                    $move = \Modules\Inventory\Models\StockMove::find($moveData['move_id']);
                    if (! $move) {
                        continue;
                    }

                    $line = \Modules\Inventory\Models\StockMoveProductLine::find($moveData['product_line_id']);
                    if (! $line) {
                        continue;
                    }

                    $actualQty = (float) $moveData['actual_quantity'];
                    \Illuminate\Support\Facades\Log::info("Updating Line {$line->id} to {$actualQty}");

                    // Update line quantity to what was actually fulfilled
                    $line->update(['quantity' => $actualQty]);

                    // Mark Move as Done
                    if (! in_array($move->id, $processedMoveIds)) {
                        $move->update(['status' => StockMoveStatus::Done]);
                        app(StockReservationService::class)->consumeForMove($move);
                        $processedMoveIds[] = $move->id;
                    }
                }

                // 4. Mark Picking as Done
                $this->record->update([
                    'state' => StockPickingState::Done,
                    'completed_at' => now(),
                ]);
                \Illuminate\Support\Facades\Log::info('Validation Transaction Commit');
            });

            Notification::make()->title('Picking Validated')->success()->send();
            $this->redirect(StockPickingResource::getUrl('view', ['record' => $this->record]));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Validation Exception: '.$e->getMessage());
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        }
    }

    protected function createBackorderRecords(array $backorderItems): void
    {
        $backorderPicking = StockPicking::create([
            'company_id' => $this->record->company_id,
            'type' => $this->record->type,
            'state' => StockPickingState::Assigned,
            'partner_id' => $this->record->partner_id,
            'scheduled_date' => now(),
            'origin' => $this->record->reference.' (Backorder)',
            'created_by_user_id' => \Illuminate\Support\Facades\Auth::id(),
            'reference' => $this->record->reference.'-BO-'.rand(100, 999),
        ]);

        $backorderMoves = [];

        foreach ($backorderItems as $item) {
            $originalMove = \Modules\Inventory\Models\StockMove::find($item['move_id']);
            $originalLine = \Modules\Inventory\Models\StockMoveProductLine::find($item['product_line_id']);

            // Reuse or Create Backorder Move
            if (! isset($backorderMoves[$originalMove->id])) {
                $newMove = $originalMove->replicate();
                $newMove->picking_id = $backorderPicking->id;
                $newMove->status = StockMoveStatus::Draft;
                $newMove->save();
                $backorderMoves[$originalMove->id] = $newMove;
            }

            $newMove = $backorderMoves[$originalMove->id];

            // Create Backorder Line
            $newLine = $originalLine->replicate();
            $newLine->stock_move_id = $newMove->id;
            $newLine->quantity = $item['backorder_qty'];
            $newLine->save();
        }
    }
}

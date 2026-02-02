<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource;
use Kezi\Inventory\Models\StockPicking;

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
                Section::make(__('inventory::stock_picking.sections.actual_quantities'))
                    ->description(__('inventory::stock_picking.sections.confirm_quantities_description'))
                    ->schema([
                        Repeater::make('moves')
                            ->label(__('inventory::stock_picking.stock_moves'))
                            ->schema([
                                TextInput::make('product_info')
                                    ->label(__('inventory::stock_picking.product'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(fn ($state) => $state['product_name'] ?? '—'),

                                TextInput::make('planned_quantity')
                                    ->label(__('inventory::stock_picking.planned_quantity'))
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(fn ($state) => number_format($state['planned_quantity'] ?? 0, 2)),

                                TextInput::make('actual_quantity')
                                    ->label(__('inventory::stock_picking.actual_fulfilled_quantity'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live() // Update backorder calcs immediately if we added that field
                                    ->default(fn ($state) => $state['actual_quantity'] ?? 0),

                                TextInput::make('lot_lines_info')
                                    ->label(__('inventory::stock_picking.assigned_lots'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(function ($state) {
                                        if (! isset($state['lot_lines']) || empty($state['lot_lines'])) {
                                            return __('inventory::stock_picking.placeholders.no_lots_assigned');
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
                ->label(__('inventory::stock_picking.validate_done'))
                ->color('success')
                ->icon('heroicon-o-check')
                ->action(function () {
                    $this->validatePicking();
                }),

            Action::make('create_backorder')
                ->label(__('inventory::stock_picking.create_backorder'))
                ->color('warning')
                ->icon('heroicon-o-clock')
                ->action('createBackorder'),
        ];
    }

    public function validatePicking(): void
    {
        // \Illuminate\Support\Facades\Log::info('validatePicking called');
        $data = $this->form->getState();
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
            Notification::make()->title(__('inventory::stock_picking.notifications.error'))->body(__('inventory::stock_picking.notifications.no_lines_to_validate'))->danger()->send();

            return;
        }

        try {
            app(\Kezi\Inventory\Actions\Inventory\ValidateStockPickingAction::class)
                ->execute($this->record, $data, $createBackorder);

            Notification::make()->title(__('inventory::stock_picking.notifications.validated'))->success()->send();
            $this->redirect(StockPickingResource::getUrl('view', ['record' => $this->record]));

        } catch (\Illuminate\Validation\ValidationException $e) {
            Notification::make()
                ->title(__('inventory::stock_picking.notifications.error'))
                ->body(implode("\n", $e->validator->errors()->all()))
                ->danger()
                ->send();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Validation Exception: '.$e->getMessage());
            Notification::make()->title(__('inventory::stock_picking.notifications.error'))->body($e->getMessage())->danger()->send();
        }
    }
}

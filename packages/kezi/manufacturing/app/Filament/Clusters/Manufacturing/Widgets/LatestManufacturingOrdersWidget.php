<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Kezi\Manufacturing\Enums\ManufacturingOrderStatus;
use Kezi\Manufacturing\Models\ManufacturingOrder;

class LatestManufacturingOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ManufacturingOrder::query()
                    ->where('company_id', auth()->user()->currentCompany->id)
                    ->whereIn('status', [
                        ManufacturingOrderStatus::Confirmed,
                        ManufacturingOrderStatus::InProgress,
                    ])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label(__('manufacturing::manufacturing.widgets.latest_orders.mo_number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('manufacturing::manufacturing.widgets.latest_orders.product'))
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('quantity_to_produce')
                    ->label(__('manufacturing::manufacturing.widgets.latest_orders.qty_to_produce'))
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\TextColumn::make('quantity_produced')
                    ->label(__('manufacturing::manufacturing.widgets.latest_orders.qty_produced'))
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('manufacturing::manufacturing.widgets.latest_orders.status'))
                    ->colors([
                        'warning' => ManufacturingOrderStatus::Confirmed->value,
                        'primary' => ManufacturingOrderStatus::InProgress->value,
                    ]),

                Tables\Columns\TextColumn::make('planned_start_date')
                    ->label(__('manufacturing::manufacturing.widgets.latest_orders.planned_start'))
                    ->date()
                    ->sortable(),
            ])
            ->heading(__('manufacturing::manufacturing.widgets.latest_orders.heading'))
            ->description(__('manufacturing::manufacturing.widgets.latest_orders.description'));
    }
}

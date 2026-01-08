<?php

namespace Modules\Manufacturing\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;

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
                    ->label('MO Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('quantity_to_produce')
                    ->label('Qty to Produce')
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\TextColumn::make('quantity_produced')
                    ->label('Qty Produced')
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => ManufacturingOrderStatus::Confirmed->value,
                        'primary' => ManufacturingOrderStatus::InProgress->value,
                    ]),

                Tables\Columns\TextColumn::make('planned_start_date')
                    ->label('Planned Start')
                    ->date()
                    ->sortable(),
            ])
            ->heading('Active Manufacturing Orders')
            ->description('Orders currently in production or ready to start');
    }
}

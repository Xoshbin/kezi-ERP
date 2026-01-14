<?php

namespace Modules\Inventory\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Modules\Inventory\Models\SerialNumber;

class WarrantyExpiringWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SerialNumber::query()
                    ->whereNotNull('warranty_end')
                    ->where('warranty_end', '>=', now())
                    ->where('warranty_end', '<=', now()->addDays(30))
                    ->with(['product', 'currentLocation', 'soldToPartner'])
                    ->orderBy('warranty_end')
            )
            ->columns([
                TextColumn::make('serial_code')
                    ->label(__('inventory.serial_code'))
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('product.name')
                    ->label(__('product.label'))
                    ->searchable(),

                TextColumn::make('soldToPartner.name')
                    ->label(__('inventory.sold_to'))
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('currentLocation.name')
                    ->label(__('inventory.current_location'))
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('warranty_end')
                    ->label(__('inventory.warranty_end'))
                    ->date()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->warranty_end->diffInDays(now()) <= 7 ? 'danger' : 'warning'),

                TextColumn::make('days_remaining')
                    ->label(__('common.days_remaining'))
                    ->getStateUsing(fn ($record) => $record->warranty_end->diffInDays(now()))
                    ->suffix(' '.__('common.days'))
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 7 => 'danger',
                        $state <= 14 => 'warning',
                        default => 'info',
                    }),
            ])
            ->heading(__('inventory.warranties_expiring_soon'))
            ->description(__('inventory.warranties_expiring_within_30_days'))
            ->emptyStateHeading(__('inventory.no_warranties_expiring'))
            ->emptyStateDescription(__('inventory.all_warranties_are_current'));
    }
}

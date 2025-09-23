<?php

namespace App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Tables;

use App\Enums\Purchases\PurchaseOrderStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->label(__('purchase_orders.fields.po_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vendor.name')
                    ->label(__('purchase_orders.fields.vendor'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('purchase_orders.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('po_date')
                    ->label(__('purchase_orders.fields.po_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('expected_delivery_date')
                    ->label(__('purchase_orders.fields.expected_delivery_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label(__('purchase_orders.fields.total_amount'))
                    ->money(fn($record) => $record->currency->code)
                    ->sortable(),

                TextColumn::make('currency.code')
                    ->label(__('purchase_orders.fields.currency'))
                    ->toggleable(),

                TextColumn::make('reference')
                    ->label(__('purchase_orders.fields.reference'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('deliveryLocation.name')
                    ->label(__('purchase_orders.fields.delivery_location'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('createdByUser.name')
                    ->label(__('purchase_orders.fields.created_by'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('purchase_orders.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('purchase_orders.fields.status'))
                    ->options(PurchaseOrderStatus::class),

                SelectFilter::make('vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('currency')
                    ->relationship('currency', 'code')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('po_date', 'desc');
    }
}

<?php

namespace Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Tables;


use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Sales\Enums\Sales\SalesOrderStatus;

class SalesOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('so_number')
                    ->label(__('sales_orders.fields.so_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label(__('sales_orders.fields.customer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('sales_orders.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('invoicing_status')
                    ->label(__('sales_orders.fields.invoicing_status'))
                    ->getStateUsing(function ($record): string {
                        $invoicesCount = $record->invoices()->count();
                        if ($invoicesCount === 0) {
                            return __('sales_orders.invoicing_status.not_invoiced');
                        } elseif ($invoicesCount === 1) {
                            return __('sales_orders.invoicing_status.invoiced');
                        } else {
                            return __('sales_orders.invoicing_status.multiple_invoices', ['count' => $invoicesCount]);
                        }
                    })
                    ->badge()
                    ->color(function ($record): string {
                        $invoicesCount = $record->invoices()->count();
                        return match ($invoicesCount) {
                            0 => 'warning',
                            1 => 'success',
                            default => 'info',
                        };
                    })
                    ->icon(function ($record): string {
                        $invoicesCount = $record->invoices()->count();
                        return match ($invoicesCount) {
                            0 => 'heroicon-m-exclamation-triangle',
                            1 => 'heroicon-m-check-circle',
                            default => 'heroicon-m-document-duplicate',
                        };
                    })
                    ->toggleable(),

                TextColumn::make('delivery_progress')
                    ->label(__('sales_orders.fields.delivery_progress'))
                    ->getStateUsing(fn($record) => number_format($record->getDeliveryProgress(), 1) . '%')
                    ->badge()
                    ->color(function ($record): string {
                        $progress = $record->getDeliveryProgress();
                        return match (true) {
                            $progress == 0 => 'gray',
                            $progress < 100 => 'warning',
                            default => 'success',
                        };
                    })
                    ->toggleable(),

                TextColumn::make('so_date')
                    ->label(__('sales_orders.fields.so_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('expected_delivery_date')
                    ->label(__('sales_orders.fields.expected_delivery_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label(__('sales_orders.fields.total_amount'))
                    ->money(fn($record) => $record->currency->code)
                    ->sortable(),

                TextColumn::make('currency.code')
                    ->label(__('sales_orders.fields.currency'))
                    ->toggleable(),

                TextColumn::make('reference')
                    ->label(__('sales_orders.fields.reference'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('deliveryLocation.name')
                    ->label(__('sales_orders.fields.delivery_location'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('createdByUser.name')
                    ->label(__('sales_orders.fields.created_by'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('sales_orders.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('sales_orders.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('sales_orders.fields.status'))
                    ->options(SalesOrderStatus::class)
                    ->multiple(),

                SelectFilter::make('customer')
                    ->label(__('sales_orders.fields.customer'))
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('currency')
                    ->label(__('sales_orders.fields.currency'))
                    ->relationship('currency', 'code')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

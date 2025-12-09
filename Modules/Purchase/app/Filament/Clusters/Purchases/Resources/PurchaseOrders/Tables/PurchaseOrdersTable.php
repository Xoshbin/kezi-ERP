<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->label(__('purchase::purchase_orders.fields.po_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vendor.name')
                    ->label(__('purchase::purchase_orders.fields.vendor'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('purchase::purchase_orders.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('billing_status')
                    ->label(__('purchase::purchase_orders.fields.billing_status'))
                    ->getStateUsing(function ($record): string {
                        $billsCount = $record->vendorBills()->count();
                        if ($billsCount === 0) {
                            return __('purchase::purchase_orders.billing_status.not_billed');
                        } elseif ($billsCount === 1) {
                            return __('purchase::purchase_orders.billing_status.billed');
                        } else {
                            return __('purchase::purchase_orders.billing_status.multiple_bills', ['count' => $billsCount]);
                        }
                    })
                    ->badge()
                    ->color(function ($record): string {
                        $billsCount = $record->vendorBills()->count();

                        return match ($billsCount) {
                            0 => 'warning',
                            1 => 'success',
                            default => 'info',
                        };
                    })
                    ->icon(function ($record): string {
                        $billsCount = $record->vendorBills()->count();

                        return match ($billsCount) {
                            0 => 'heroicon-m-exclamation-triangle',
                            1 => 'heroicon-m-check-circle',
                            default => 'heroicon-m-document-duplicate',
                        };
                    })
                    ->toggleable(),

                TextColumn::make('po_date')
                    ->label(__('purchase::purchase_orders.fields.po_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('expected_delivery_date')
                    ->label(__('purchase::purchase_orders.fields.expected_delivery_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label(__('purchase::purchase_orders.fields.total_amount'))
                    ->money(fn ($record) => $record->currency->code)
                    ->sortable(),

                TextColumn::make('currency.code')
                    ->label(__('purchase::purchase_orders.fields.currency'))
                    ->toggleable(),

                TextColumn::make('reference')
                    ->label(__('purchase::purchase_orders.fields.reference'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('deliveryLocation.name')
                    ->label(__('purchase::purchase_orders.fields.delivery_location'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('createdByUser.name')
                    ->label(__('purchase::purchase_orders.fields.created_by'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('purchase::purchase_orders.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('purchase::purchase_orders.fields.status'))
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

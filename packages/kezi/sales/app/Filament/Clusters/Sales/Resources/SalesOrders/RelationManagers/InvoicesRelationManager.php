<?php

namespace Kezi\Sales\Filament\Clusters\Sales\Resources\SalesOrders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * @extends RelationManager<\Kezi\Sales\Models\SalesOrder>
 */
class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label(__('sales::invoice.invoice_number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('sales::invoice.status'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label(__('sales::invoice.invoice_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('sales::invoice.due_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('sales::invoice.total_amount'))
                    ->money(fn ($record) => $record->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('sales::invoice.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Invoice creation is handled by the main page action
            ])
            ->defaultSort('created_at', 'desc');
    }
}

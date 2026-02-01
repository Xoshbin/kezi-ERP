<?php

namespace Jmeryar\Sales\Filament\Clusters\Sales\Resources\SalesOrders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

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
                    ->label(__('invoices.fields.invoice_number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('invoices.fields.status'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label(__('invoices.fields.invoice_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('invoices.fields.due_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('invoices.fields.total_amount'))
                    ->money(fn ($record) => $record->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('invoices.fields.created_at'))
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

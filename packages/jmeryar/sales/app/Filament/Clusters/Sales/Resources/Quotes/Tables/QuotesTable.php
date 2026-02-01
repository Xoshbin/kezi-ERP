<?php

namespace Jmeryar\Sales\Filament\Clusters\Sales\Resources\Quotes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Jmeryar\Sales\Enums\Sales\QuoteStatus;

class QuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quote_number')
                    ->label(__('sales::quote.fields.quote_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('partner.name')
                    ->label(__('sales::quote.fields.partner'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('sales::quote.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('version')
                    ->label(__('sales::quote.fields.version'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => "v{$state}")
                    ->toggleable(),

                TextColumn::make('quote_date')
                    ->label(__('sales::quote.fields.quote_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('valid_until')
                    ->label(__('sales::quote.fields.valid_until'))
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),

                TextColumn::make('total')
                    ->label(__('sales::quote.fields.total'))
                    ->money(fn ($record) => $record->currency->code ?? 'IQD')
                    ->sortable(),

                TextColumn::make('currency.code')
                    ->label(__('sales::quote.fields.currency'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('createdBy.name')
                    ->label(__('sales::quote.fields.created_by'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('sales::quote.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('sales::quote.fields.status'))
                    ->options(QuoteStatus::class)
                    ->multiple(),

                SelectFilter::make('partner')
                    ->label(__('sales::quote.fields.partner'))
                    ->relationship('partner', 'name')
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

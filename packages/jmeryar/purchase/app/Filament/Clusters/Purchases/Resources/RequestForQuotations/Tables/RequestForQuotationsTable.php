<?php

namespace Jmeryar\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Jmeryar\Purchase\Models\RequestForQuotation;

class RequestForQuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfq_number')
                    ->label(__('purchase::request_for_quotation.fields.rfq_number'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label(__('purchase::request_for_quotation.fields.vendor'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rfq_date')
                    ->label(__('purchase::request_for_quotation.fields.date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label(__('purchase::request_for_quotation.fields.valid_until'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label(__('purchase::request_for_quotation.fields.total'))
                    ->money(fn (RequestForQuotation $record) => $record->currency->code),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()
                    ->url(fn (\Jmeryar\Purchase\Models\RequestForQuotation $record): string => \Jmeryar\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource::getUrl('view', ['record' => $record])),
                \Filament\Actions\EditAction::make()
                    ->url(fn (\Jmeryar\Purchase\Models\RequestForQuotation $record): string => \Jmeryar\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\RequestForQuotationResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

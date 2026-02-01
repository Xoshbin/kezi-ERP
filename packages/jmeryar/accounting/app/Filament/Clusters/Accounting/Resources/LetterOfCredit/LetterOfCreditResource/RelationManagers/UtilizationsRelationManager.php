<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Jmeryar\Payment\Models\LCUtilization;

class UtilizationsRelationManager extends RelationManager
{
    protected static string $relationship = 'utilizations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::lc.lc_utilizations');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('vendor_bill_id')
                    ->label(__('accounting::lc.vendor_bill'))
                    ->url(fn (LCUtilization $record) => route('filament.jmeryar.resources.vendor-bills.edit', $record->vendor_bill_id))
                    ->color('primary'),

                Tables\Columns\TextColumn::make('vendorBill.bill_number')
                    ->label(__('accounting::lc.bill_number'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('utilized_amount')
                    ->money(fn (LCUtilization $record) => $record->letterOfCredit->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('utilization_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // TODO: Add action to utilize LC against vendor bill
            ])
            ->actions([
                // View action can be added later
            ])
            ->bulkActions([
                //
            ]);
    }
}

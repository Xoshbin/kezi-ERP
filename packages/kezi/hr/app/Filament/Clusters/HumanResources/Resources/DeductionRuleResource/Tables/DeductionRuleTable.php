<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeductionRuleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('hr::payroll.deduction_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label(__('hr::payroll.deduction_code'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('hr::payroll.deduction_type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'info',
                        'fixed_amount' => 'success',
                    }),

                TextColumn::make('value')
                    ->label(__('hr::payroll.percentage_value'))
                    ->getStateUsing(fn ($record) => $record->type === 'percentage' ? ($record->value * 100).'%' : '-'),

                TextColumn::make('amount')
                    ->label(__('hr::payroll.fixed_amount'))
                    ->money(fn ($record) => $record->currency_code),

                IconColumn::make('is_statutory')
                    ->label(__('hr::payroll.is_statutory'))
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label(__('hr::payroll.is_active'))
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

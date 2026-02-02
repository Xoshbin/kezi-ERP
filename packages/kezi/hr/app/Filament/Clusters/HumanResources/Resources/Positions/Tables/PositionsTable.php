<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Positions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PositionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('hr::position.title'))
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label(__('hr::position.department'))
                    ->searchable(),
                TextColumn::make('employment_type')
                    ->label(__('hr::position.employment_type'))
                    ->searchable(),
                TextColumn::make('level')
                    ->label(__('hr::position.level'))
                    ->searchable(),
                TextColumn::make('min_salary')
                    ->label(__('hr::position.min_salary'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_salary')
                    ->label(__('hr::position.max_salary'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('salaryCurrency.name')
                    ->label(__('hr::position.salary_currency'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('hr::position.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('common.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

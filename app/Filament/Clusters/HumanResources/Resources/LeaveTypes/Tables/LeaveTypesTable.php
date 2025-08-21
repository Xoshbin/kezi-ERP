<?php

namespace App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaveTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('default_days_per_year')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('requires_approval')
                    ->boolean(),
                IconColumn::make('is_paid')
                    ->boolean(),
                IconColumn::make('carries_forward')
                    ->boolean(),
                TextColumn::make('max_carry_forward_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_consecutive_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('min_notice_days')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('requires_documentation')
                    ->boolean(),
                TextColumn::make('color')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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

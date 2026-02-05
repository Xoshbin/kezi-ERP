<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\Tables;

use \Filament\Actions\BulkActionGroup;
use \Filament\Actions\DeleteBulkAction;
use \Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaveTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('hr::leave_type.name'))
                    ->searchable(),
                TextColumn::make('code')
                    ->label(__('hr::leave_type.code'))
                    ->searchable(),
                TextColumn::make('default_days_per_year')
                    ->label(__('hr::leave_type.default_days_per_year'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('requires_approval')
                    ->label(__('hr::leave_type.requires_approval'))
                    ->boolean(),
                IconColumn::make('is_paid')
                    ->label(__('hr::leave_type.is_paid'))
                    ->boolean(),
                IconColumn::make('carries_forward')
                    ->label(__('hr::leave_type.carries_forward'))
                    ->boolean(),
                TextColumn::make('max_carry_forward_days')
                    ->label(__('hr::leave_type.max_carry_forward_days'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_consecutive_days')
                    ->label(__('hr::leave_type.max_consecutive_days'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('min_notice_days')
                    ->label(__('hr::leave_type.min_notice_days'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('requires_documentation')
                    ->label(__('hr::leave_type.requires_documentation'))
                    ->boolean(),
                TextColumn::make('color')
                    ->label(__('hr::leave_type.color'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('hr::leave_type.is_active'))
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

<?php

declare(strict_types=1);

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Attendances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label(__('hr::attendance.employee'))
                    ->sortable()
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('attendance_date')
                    ->label(__('hr::attendance.attendance_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('clock_in_time')
                    ->label(__('hr::attendance.clock_in_time'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('clock_out_time')
                    ->label(__('hr::attendance.clock_out_time'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('hr::attendance.status'))
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

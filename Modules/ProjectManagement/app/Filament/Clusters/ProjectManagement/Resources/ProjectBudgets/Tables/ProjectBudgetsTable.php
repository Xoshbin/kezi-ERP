<?php

namespace Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectBudgets\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectBudgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('budget_amount')
                    ->label('Total Budget')
                    ->money(fn ($record) => $record->company->currency->code ?? 'USD') // Assumes attribute is major units if using MoneyCast, OR need manual formatting if minor
                    // ProjectBudget line uses minor units. ProjectBudget budget_amount also minor?
                    // Observer updates it by summing lines (minor).
                    // So we need:
                    ->formatStateUsing(fn ($state, $record) => $record->company ? number_format($state / 100, 2) : $state)
                    ->prefix(fn ($record) => $record->company->currency->symbol ?? '$')
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

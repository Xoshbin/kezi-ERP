<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Schemas\ExpenseReportForm;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Tables\ExpenseReportsTable;

class ExpenseReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'expenseReports';

    protected static ?string $recordTitleAttribute = 'report_number';

    public function form(Schema $schema): Schema
    {
        return ExpenseReportForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        // Reuse ExpenseReportsTable configuration if possible, or define simple table
        return $table
            ->recordTitleAttribute('report_number')
            ->columns([
                Tables\Columns\TextColumn::make('report_number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money(fn ($record) => $record->cashAdvance->currency->code ?? 'IQD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

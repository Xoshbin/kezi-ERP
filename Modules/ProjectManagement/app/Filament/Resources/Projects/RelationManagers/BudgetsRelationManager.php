<?php

namespace Modules\ProjectManagement\Filament\Resources\Projects\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ProgressColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BudgetsRelationManager extends RelationManager
{
    protected static string $relationship = 'budgets';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('end_date')
                    ->required(),
                TextInput::make('total_budget')
                    ->numeric()
                    ->required()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_budget')
                    ->money(fn ($record) => $record->company->currency->code ?? 'USD')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('total_actual')
                    ->money(fn ($record) => $record->company->currency->code ?? 'USD')
                    ->sortable()
                    ->alignEnd(),
                ProgressColumn::make('utilization_percentage')
                    ->label('Utilization'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make(),
                \Filament\Tables\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

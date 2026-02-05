<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ProgressColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kezi\ProjectManagement\Enums\TaskStatus;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('assigned_to')
                    ->relationship('assignedTo', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->options(TaskStatus::class)
                    ->default(TaskStatus::Pending)
                    ->required(),
                DatePicker::make('due_date'),
                TextInput::make('estimated_hours')
                    ->numeric()
                    ->default(0),
                TextInput::make('progress_percentage')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assignedTo.full_name') // Assuming we have or use concatenation
                    ->label(__('projectmanagement::project.form.labels.assigned_to')),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('estimated_hours')
                    ->numeric(2),
                TextColumn::make('actual_hours')
                    ->numeric(2),
                ProgressColumn::make('progress_percentage')
                    ->label(__('projectmanagement::project.form.labels.progress')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

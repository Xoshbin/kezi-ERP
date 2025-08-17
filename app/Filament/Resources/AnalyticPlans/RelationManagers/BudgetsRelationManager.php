<?php

namespace App\Filament\Resources\AnalyticPlans\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BudgetsRelationManager extends RelationManager
{
    protected static string $relationship = 'budgets';

    protected static ?string $title = 'Budgets';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('analytic_plan.name'))
                    ->required()
                    ->maxLength(255),
                DatePicker::make('period_start_date')
                    ->label(__('analytic_plan.period_start_date'))
                    ->required(),
                DatePicker::make('period_end_date')
                    ->label(__('analytic_plan.period_end_date'))
                    ->required(),
                TextInput::make('budget_type')
                    ->label(__('analytic_plan.budget_type'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('status')
                    ->label(__('analytic_plan.status'))
                    ->required()
                    ->maxLength(255)
                    ->default('Draft'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('analytic_plan.name')),
                TextColumn::make('period_start_date')
                    ->label(__('analytic_plan.period_start_date'))
                    ->date(),
                TextColumn::make('period_end_date')
                    ->label(__('analytic_plan.period_end_date'))
                    ->date(),
                TextColumn::make('budget_type')
                    ->label(__('analytic_plan.budget_type')),
                TextColumn::make('status')
                    ->label(__('analytic_plan.status')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
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

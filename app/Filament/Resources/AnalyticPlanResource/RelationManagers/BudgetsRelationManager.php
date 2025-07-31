<?php

namespace App\Filament\Resources\AnalyticPlanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BudgetsRelationManager extends RelationManager
{
    protected static string $relationship = 'budgets';

    protected static ?string $title = 'Budgets';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('analytic_plan.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('period_start_date')
                    ->label(__('analytic_plan.period_start_date'))
                    ->required(),
                Forms\Components\DatePicker::make('period_end_date')
                    ->label(__('analytic_plan.period_end_date'))
                    ->required(),
                Forms\Components\TextInput::make('budget_type')
                    ->label(__('analytic_plan.budget_type'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
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
                Tables\Columns\TextColumn::make('name')
                    ->label(__('analytic_plan.name')),
                Tables\Columns\TextColumn::make('period_start_date')
                    ->label(__('analytic_plan.period_start_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('period_end_date')
                    ->label(__('analytic_plan.period_end_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('budget_type')
                    ->label(__('analytic_plan.budget_type')),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('analytic_plan.status')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
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

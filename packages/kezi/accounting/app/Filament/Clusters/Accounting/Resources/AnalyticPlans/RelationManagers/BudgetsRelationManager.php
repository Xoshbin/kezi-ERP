<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\RelationManagers;

use \Filament\Actions\BulkActionGroup;
use \Filament\Actions\CreateAction;
use \Filament\Actions\DeleteAction;
use \Filament\Actions\DeleteBulkAction;
use \Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends RelationManager<\Kezi\Accounting\Models\AnalyticPlan>
 */
class BudgetsRelationManager extends RelationManager
{
    protected static string $relationship = 'budgets';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::budget.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('accounting::analytic_plan.name'))
                    ->required()
                    ->maxLength(255),
                DatePicker::make('period_start_date')
                    ->label(__('accounting::analytic_plan.period_start_date'))
                    ->required(),
                DatePicker::make('period_end_date')
                    ->label(__('accounting::analytic_plan.period_end_date'))
                    ->required(),
                TextInput::make('budget_type')
                    ->label(__('accounting::analytic_plan.budget_type'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('status')
                    ->label(__('accounting::analytic_plan.status'))
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
                    ->label(__('accounting::analytic_plan.name')),
                TextColumn::make('period_start_date')
                    ->label(__('accounting::analytic_plan.period_start_date'))
                    ->date(),
                TextColumn::make('period_end_date')
                    ->label(__('accounting::analytic_plan.period_end_date'))
                    ->date(),
                TextColumn::make('budget_type')
                    ->label(__('accounting::analytic_plan.budget_type')),
                TextColumn::make('status')
                    ->label(__('accounting::analytic_plan.status')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

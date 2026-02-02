<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Budgets\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BudgetLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetLines';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::budget.budget_lines.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('analytic_account_id')
                    ->label(__('accounting::budget.budget_lines.form.analytic_account_id'))
                    ->relationship('analyticAccount', 'name'),
                Select::make('account_id')
                    ->label(__('accounting::budget.budget_lines.form.account_id'))
                    ->relationship('account', 'name'),
                TextInput::make('budgeted_amount')
                    ->label(__('accounting::budget.budget_lines.form.budgeted_amount'))
                    ->required()
                    ->numeric(),
                TextInput::make('achieved_amount')
                    ->label(__('accounting::budget.budget_lines.form.achieved_amount'))
                    ->required()
                    ->numeric(),
                TextInput::make('committed_amount')
                    ->label(__('accounting::budget.budget_lines.form.committed_amount'))
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('analyticAccount.name')
                    ->label(__('accounting::budget.budget_lines.table.analytic_account_name')),
                TextColumn::make('account.name')
                    ->label(__('accounting::budget.budget_lines.table.account_name')),
                TextColumn::make('budgeted_amount')
                    ->label(__('accounting::budget.budget_lines.table.budgeted_amount')),
                TextColumn::make('achieved_amount')
                    ->label(__('accounting::budget.budget_lines.table.achieved_amount')),
                TextColumn::make('committed_amount')
                    ->label(__('accounting::budget.budget_lines.table.committed_amount')),
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

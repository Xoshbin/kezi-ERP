<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Budgets;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Budgets\Pages\CreateBudget;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Budgets\Pages\EditBudget;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Budgets\Pages\ListBudgets;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Budgets\RelationManagers\BudgetLinesRelationManager;
use Modules\Accounting\Models\Budget;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.financial_planning');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::budget.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::budget.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::budget.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label(__('accounting::budget.form.company_id'))
                    ->relationship('company', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label(__('accounting::budget.form.name'))
                    ->required()
                    ->maxLength(255),
                DatePicker::make('period_start_date')
                    ->label(__('accounting::budget.form.period_start_date'))
                    ->required(),
                DatePicker::make('period_end_date')
                    ->label(__('accounting::budget.form.period_end_date'))
                    ->required(),
                TextInput::make('budget_type')
                    ->label(__('accounting::budget.form.budget_type'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('status')
                    ->label(__('accounting::budget.form.status'))
                    ->required()
                    ->maxLength(255)
                    ->default(__('accounting::budget.form.default_status')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('accounting::budget.table.company_name'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('accounting::budget.table.name'))
                    ->searchable(),
                TextColumn::make('period_start_date')
                    ->label(__('accounting::budget.table.period_start_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('period_end_date')
                    ->label(__('accounting::budget.table.period_end_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('budget_type')
                    ->label(__('accounting::budget.table.budget_type'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('accounting::budget.table.status'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('accounting::budget.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('accounting::budget.table.updated_at'))
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

    public static function getRelations(): array
    {
        return [
            BudgetLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgets::route('/'),
            'create' => CreateBudget::route('/create'),
            'edit' => EditBudget::route('/{record}/edit'),
        ];
    }
}

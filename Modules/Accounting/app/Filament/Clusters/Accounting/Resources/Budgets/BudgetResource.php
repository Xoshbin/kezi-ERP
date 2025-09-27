<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Budgets;

use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\Budgets\Pages\CreateBudget;
use App\Filament\Clusters\Accounting\Resources\Budgets\Pages\EditBudget;
use App\Filament\Clusters\Accounting\Resources\Budgets\Pages\ListBudgets;
use App\Filament\Clusters\Accounting\Resources\Budgets\RelationManagers\BudgetLinesRelationManager;
use App\Models\Budget;
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

class BudgetResource extends Resource
{
    protected static ?string $model = \Modules\Accounting\Models\Budget::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.financial_planning');
    }

    public static function getModelLabel(): string
    {
        return __('budget.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('budget.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('budget.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label(__('budget.form.company_id'))
                    ->relationship('company', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label(__('budget.form.name'))
                    ->required()
                    ->maxLength(255),
                DatePicker::make('period_start_date')
                    ->label(__('budget.form.period_start_date'))
                    ->required(),
                DatePicker::make('period_end_date')
                    ->label(__('budget.form.period_end_date'))
                    ->required(),
                TextInput::make('budget_type')
                    ->label(__('budget.form.budget_type'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('status')
                    ->label(__('budget.form.status'))
                    ->required()
                    ->maxLength(255)
                    ->default(__('budget.form.default_status')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('budget.table.company_name'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('budget.table.name'))
                    ->searchable(),
                TextColumn::make('period_start_date')
                    ->label(__('budget.table.period_start_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('period_end_date')
                    ->label(__('budget.table.period_end_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('budget_type')
                    ->label(__('budget.table.budget_type'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('budget.table.status'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('budget.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('budget.table.updated_at'))
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

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BudgetResource\Pages;
use App\Filament\Resources\BudgetResource\RelationManagers;
use App\Models\Budget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->label(__('budget.form.company_id'))
                    ->relationship('company', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label(__('budget.form.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('period_start_date')
                    ->label(__('budget.form.period_start_date'))
                    ->required(),
                Forms\Components\DatePicker::make('period_end_date')
                    ->label(__('budget.form.period_end_date'))
                    ->required(),
                Forms\Components\TextInput::make('budget_type')
                    ->label(__('budget.form.budget_type'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
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
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('budget.table.company_name'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('budget.table.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('period_start_date')
                    ->label(__('budget.table.period_start_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_end_date')
                    ->label(__('budget.table.period_end_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('budget_type')
                    ->label(__('budget.table.budget_type'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('budget.table.status'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('budget.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('budget.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BudgetLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
        ];
    }
}

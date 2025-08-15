<?php

namespace App\Filament\Resources\AnalyticPlans;

use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\AnalyticPlans\RelationManagers\AnalyticAccountsRelationManager;
use App\Filament\Resources\AnalyticPlans\RelationManagers\BudgetsRelationManager;
use App\Filament\Resources\AnalyticPlans\Pages\ListAnalyticPlans;
use App\Filament\Resources\AnalyticPlans\Pages\CreateAnalyticPlan;
use App\Filament\Resources\AnalyticPlans\Pages\EditAnalyticPlan;
use App\Filament\Resources\AnalyticPlanResource\Pages;
use App\Filament\Resources\AnalyticPlanResource\RelationManagers;
use App\Models\AnalyticPlan;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AnalyticPlanResource extends Resource
{
    use Translatable;

    protected static ?string $model = AnalyticPlan::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.core_accounting');
    }

    public static function getModelLabel(): string
    {
        return __('analytic_plan.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('analytic_plan.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('analytic_plan.company'))
                    ->required(),
                TextInput::make('name')
                    ->label(__('analytic_plan.name'))
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('analytic_plan.company'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('analytic_plan.name'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('analytic_plan.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('analytic_plan.updated_at'))
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
            AnalyticAccountsRelationManager::class,
            BudgetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnalyticPlans::route('/'),
            'create' => CreateAnalyticPlan::route('/create'),
            'edit' => EditAnalyticPlan::route('/{record}/edit'),
        ];
    }
}

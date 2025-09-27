<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans;

use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\CreateAnalyticPlan;
use App\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\EditAnalyticPlan;
use App\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\ListAnalyticPlans;
use App\Filament\Clusters\Accounting\Resources\AnalyticPlans\RelationManagers\AnalyticAccountsRelationManager;
use App\Filament\Clusters\Accounting\Resources\AnalyticPlans\RelationManagers\BudgetsRelationManager;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class AnalyticPlanResource extends Resource
{
    use Translatable;

    protected static ?string $model = \Modules\Accounting\Models\AnalyticPlan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = AccountingCluster::class;

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

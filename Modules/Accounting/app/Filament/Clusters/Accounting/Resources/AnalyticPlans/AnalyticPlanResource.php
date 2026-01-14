<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans;

use BackedEnum;
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
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\CreateAnalyticPlan;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\EditAnalyticPlan;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\ListAnalyticPlans;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\RelationManagers\AnalyticAccountsRelationManager;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\RelationManagers\BudgetsRelationManager;
use Modules\Accounting\Models\AnalyticPlan;

class AnalyticPlanResource extends Resource
{
    use Translatable;

    protected static ?string $model = AnalyticPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.core_accounting');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::analytic_plan.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::analytic_plan.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('accounting::analytic_plan.company'))
                    ->required(),
                TextInput::make('name')
                    ->label(__('accounting::analytic_plan.name'))
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('accounting::analytic_plan.company'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('accounting::analytic_plan.name'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('accounting::analytic_plan.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('accounting::analytic_plan.updated_at'))
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

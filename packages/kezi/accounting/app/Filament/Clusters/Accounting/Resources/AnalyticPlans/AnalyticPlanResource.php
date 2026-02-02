<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans;

use App\Filament\Clusters\Settings\SettingsCluster;
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
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\CreateAnalyticPlan;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\EditAnalyticPlan;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\Pages\ListAnalyticPlans;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\RelationManagers\AnalyticAccountsRelationManager;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\RelationManagers\BudgetsRelationManager;
use Kezi\Accounting\Models\AnalyticPlan;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class AnalyticPlanResource extends Resource
{
    use Translatable;

    protected static ?string $model = AnalyticPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Configuration');
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
                \Filament\Forms\Components\Hidden::make('company_id')
                    ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
                Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->label(__('accounting::analytic_plan.parent'))
                    ->searchable()
                    ->placeholder(__('accounting::analytic_plan.select_parent')),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
    }
}

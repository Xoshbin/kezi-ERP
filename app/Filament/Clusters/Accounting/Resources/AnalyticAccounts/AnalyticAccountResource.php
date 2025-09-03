<?php

namespace App\Filament\Clusters\Accounting\Resources\AnalyticAccounts;

use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages\CreateAnalyticAccount;
use App\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages\EditAnalyticAccount;
use App\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages\ListAnalyticAccounts;
use App\Filament\Clusters\Accounting\Resources\AnalyticAccounts\RelationManagers\AnalyticPlansRelationManager;
use App\Filament\Clusters\Accounting\Resources\AnalyticAccounts\RelationManagers\JournalEntryLinesRelationManager;
use App\Filament\Support\TranslatableSelect;
use App\Models\AnalyticAccount;
use App\Models\Currency;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnalyticAccountResource extends Resource
{
    protected static ?string $model = AnalyticAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.core_accounting');
    }

    public static function getNavigationLabel(): string
    {
        return __('analytic_account.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('analytic_account.analytic_account');
    }

    public static function getPluralModelLabel(): string
    {
        return __('analytic_account.analytic_accounts');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('analytic_account.company'))
                    ->placeholder(__('analytic_account.select_company'))
                    ->required(),
                TranslatableSelect::make('currency_id', Currency::class, __('analytic_account.currency')),
                TextInput::make('name')
                    ->label(__('analytic_account.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('reference')
                    ->label(__('analytic_account.reference'))
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label(__('analytic_account.is_active'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('analytic_account.company'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency.name')
                    ->label(__('analytic_account.currency'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('analytic_account.name'))
                    ->searchable(),
                TextColumn::make('reference')
                    ->label(__('analytic_account.reference'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('analytic_account.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('analytic_account.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('analytic_account.updated_at'))
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
            JournalEntryLinesRelationManager::class,
            AnalyticPlansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnalyticAccounts::route('/'),
            'create' => CreateAnalyticAccount::route('/create'),
            'edit' => EditAnalyticAccount::route('/{record}/edit'),
        ];
    }
}

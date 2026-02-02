<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
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
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages\CreateAnalyticAccount;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages\EditAnalyticAccount;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages\ListAnalyticAccounts;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\RelationManagers\AnalyticPlansRelationManager;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\RelationManagers\JournalEntryLinesRelationManager;
use Kezi\Accounting\Models\AnalyticAccount;
use Kezi\Foundation\Models\Currency;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class AnalyticAccountResource extends Resource
{
    protected static ?string $model = AnalyticAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::analytic_account.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::analytic_account.analytic_account');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::analytic_account.analytic_accounts');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('accounting::analytic_account.company'))
                    ->placeholder(__('accounting::analytic_account.select_company'))
                    ->required(),
                TranslatableSelect::forModel('currency_id', Currency::class)
                    ->label(__('accounting::analytic_account.currency'))
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\Hidden::make('company_id')
                    ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
                TextInput::make('name')
                    ->label(__('accounting::analytic_account.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('reference')
                    ->label(__('accounting::analytic_account.reference'))
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label(__('accounting::analytic_account.is_active'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('accounting::analytic_account.company'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency.name')
                    ->label(__('accounting::analytic_account.currency'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('accounting::analytic_account.name'))
                    ->searchable(),
                TextColumn::make('reference')
                    ->label(__('accounting::analytic_account.reference'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('accounting::analytic_account.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('accounting::analytic_account.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('accounting::analytic_account.updated_at'))
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
    }
}

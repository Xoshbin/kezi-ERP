<?php

namespace App\Filament\Resources\Companies;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Filament\Resources\Companies\RelationManagers\AccountsRelationManager;
use App\Filament\Resources\Companies\RelationManagers\UsersRelationManager;
use App\Models\Company;
use App\Models\Currency;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    public static null|string $tenantOwnershipRelationshipName = 'users';

    protected static ?string $model = Company::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getModelLabel(): string
    {
        return __('company.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('company.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('company.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('address')
                    ->label(__('company.address'))
                    ->columnSpanFull(),
                TextInput::make('tax_id')
                    ->label(__('company.tax_id'))
                    ->maxLength(255),
                Select::make('currency_id')
                    ->label(__('company.currency_id'))
                    ->relationship('currency', 'name')
                    ->required()
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('code')
                            ->label(__('currency.code'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label(__('currency.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('symbol')
                            ->label(__('currency.symbol'))
                            ->required()
                            ->maxLength(5),
                        TextInput::make('exchange_rate')
                            ->label(__('currency.exchange_rate'))
                            ->required()
                            ->numeric()
                            ->default(1),
                        Toggle::make('is_active')
                            ->label(__('currency.is_active'))
                            ->required()
                            ->default(true),
                    ])
                    ->createOptionModalHeading(__('common.modal_title_create_currency'))
                    ->createOptionAction(function (Action $action) {
                        return $action
                            ->modalWidth('lg');
                    }),
                TextInput::make('fiscal_country')
                    ->label(__('company.fiscal_country'))
                    ->required()
                    ->maxLength(255),
                Select::make('parent_company_id')
                    ->label(__('company.parent_company_id'))
                    ->relationship('parentCompany', 'name')
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label(__('company.name'))
                            ->required()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label(__('company.address'))
                            ->columnSpanFull(),
                        TextInput::make('tax_id')
                            ->label(__('company.tax_id'))
                            ->maxLength(255),
                        Select::make('currency_id')
                            ->label(__('company.currency_id'))
                            ->relationship('currency', 'name')
                            ->required(),
                        TextInput::make('fiscal_country')
                            ->label(__('company.fiscal_country'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->createOptionModalHeading(__('common.modal_title_create_company'))
                    ->createOptionAction(function (Action $action) {
                        return $action
                            ->modalWidth('lg');
                    }),
                Select::make('default_accounts_payable_id')
                    ->relationship('defaultAccountsPayable', 'name')
                    ->searchable()
                    ->label(__('company.default_accounts_payable')),
                Select::make('default_tax_receivable_id')
                    ->relationship('defaultTaxReceivable', 'name')
                    ->searchable()
                    ->label(__('company.default_tax_receivable')),
                Select::make('default_purchase_journal_id')
                    ->relationship('defaultPurchaseJournal', 'name')
                    ->searchable()
                    ->label(__('company.default_purchase_journal')),
                Select::make('default_accounts_receivable_id')
                    ->relationship('defaultAccountsReceivable', 'name')
                    ->searchable()
                    ->label(__('company.default_accounts_receivable')),
                Select::make('default_sales_discount_account_id')
                    ->relationship('defaultSalesDiscountAccount', 'name')
                    ->searchable()
                    ->label(__('company.default_sales_discount_account')),
                Select::make('default_tax_account_id')
                    ->relationship('defaultTaxAccount', 'name')
                    ->searchable()
                    ->label(__('company.default_tax_account')),
                Select::make('default_sales_journal_id')
                    ->relationship('defaultSalesJournal', 'name')
                    ->searchable()
                    ->label(__('company.default_sales_journal')),
                Select::make('default_depreciation_journal_id')
                    ->relationship('defaultDepreciationJournal', 'name')
                    ->searchable()
                    ->label(__('company.default_depreciation_journal')),
                Select::make('default_bank_account_id')
                    ->relationship('defaultBankAccount', 'name')
                    ->searchable()
                    ->label(__('company.default_bank_account')),
                Select::make('default_outstanding_receipts_account_id')
                    ->relationship('defaultOutstandingReceiptsAccount', 'name')
                    ->searchable()
                    ->label(__('company.default_outstanding_receipts_account')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('company.name'))
                    ->searchable(),
                TextColumn::make('tax_id')
                    ->label(__('company.tax_id'))
                    ->searchable(),
                TextColumn::make('currency.name')
                    ->label(__('company.currency_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fiscal_country')
                    ->label(__('company.fiscal_country'))
                    ->searchable(),
                TextColumn::make('parentCompany.name')
                    ->label(__('company.parent_company_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('company.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('company.updated_at'))
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
            AccountsRelationManager::class,
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}

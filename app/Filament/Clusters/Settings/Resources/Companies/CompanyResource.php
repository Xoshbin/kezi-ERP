<?php

namespace App\Filament\Clusters\Settings\Resources\Companies;

use App\Filament\Clusters\Settings\Resources\Companies\Pages\CreateCompany;
use App\Filament\Clusters\Settings\Resources\Companies\Pages\EditCompany;
use App\Filament\Clusters\Settings\Resources\Companies\Pages\ListCompanies;
use App\Filament\Clusters\Settings\Resources\Companies\RelationManagers\AccountsRelationManager;
use App\Filament\Clusters\Settings\Resources\Companies\RelationManagers\UsersRelationManager;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Company;
use App\Models\Currency;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class CompanyResource extends Resource
{
    public static ?string $tenantOwnershipRelationshipName = 'users';

    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

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
                Section::make(__('company.section.details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('company.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('fiscal_country')
                            ->label(__('company.fiscal_country'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('tax_id')
                            ->label(__('company.tax_id'))
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label(__('company.address'))
                            ->columnSpanFull(),
                        TranslatableSelect::forModel('currency_id', Currency::class)
                            ->label(__('company.currency_id'))
                            ->searchable()
                            ->preload()
                            ->required()
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
                            ->createOptionAction(fn (Action $action) => $action->name('create-currency-option')->modalWidth('lg')),
                        Toggle::make('enable_reconciliation')
                            ->label(__('company.enable_reconciliation'))
                            ->helperText(__('company.enable_reconciliation_help'))
                            ->default(false),
                        TranslatableSelect::make('parent_company_id')
                            ->relationship('parentCompany', 'name')
                            ->label(__('company.parent_company_id'))
                            ->searchableFields(['name'])
                            ->preload()
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
                                TranslatableSelect::make('currency_id')
                                    ->relationship('currency', 'name')
                                    ->label(__('company.currency_id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('fiscal_country')
                                    ->label(__('company.fiscal_country'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_company'))
                            ->createOptionAction(fn (Action $action) => $action->name('create-company-option')->modalWidth('lg')),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('company.section.defaults'))
                    ->schema([
                        TranslatableSelect::make('default_accounts_payable_id')
                            ->relationship('defaultAccountsPayable', 'name')
                            ->label(__('company.default_accounts_payable'))
                            ->searchable()
                            ->preload(),
                        TranslatableSelect::make('default_tax_receivable_id')
                            ->relationship('defaultTaxReceivable', 'name')
                            ->label(__('company.default_tax_receivable'))
                            ->searchable()
                            ->preload(),
                        TranslatableSelect::make('default_purchase_journal_id')
                            ->relationship('defaultPurchaseJournal', 'name')
                            ->label(__('company.default_purchase_journal'))
                            ->searchable()
                            ->preload(),
                        TranslatableSelect::make('default_accounts_receivable_id')
                            ->relationship('defaultAccountsReceivable', 'name')
                            ->label(__('company.default_accounts_receivable'))
                            ->searchable()
                            ->preload(),
                        TranslatableSelect::make('default_sales_discount_account_id')
                            ->relationship('defaultSalesDiscountAccount', 'name')
                            ->label(__('company.default_sales_discount_account'))
                            ->searchable()
                            ->preload(),
                        TranslatableSelect::make('default_tax_account_id')
                            ->relationship('defaultTaxAccount', 'name')
                            ->label(__('company.default_tax_account'))
                            ->searchable()
                            ->preload(),
                        TranslatableSelect::make('default_sales_journal_id')
                            ->relationship('defaultSalesJournal', 'name')
                            ->label(__('company.default_sales_journal'))
                            ->searchable()
                            ->preload(),
                        TranslatableSelect::make('default_depreciation_journal_id')
                            ->relationship('defaultDepreciationJournal', 'name')
                            ->label(__('company.default_depreciation_journal'))
                            ->searchable()
                            ->preload(),
                        TranslatableSelect::make('default_bank_account_id')
                            ->relationship('defaultBankAccount', 'name')
                            ->label(__('company.default_bank_account'))
                            ->searchable()
                            ->preload(),
                        TranslatableSelect::make('default_outstanding_receipts_account_id')
                            ->relationship('defaultOutstandingReceiptsAccount', 'name')
                            ->label(__('company.default_outstanding_receipts_account'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
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
                    ->sortable(),
                TextColumn::make('fiscal_country')
                    ->label(__('company.fiscal_country'))
                    ->searchable(),
                TextColumn::make('parentCompany.name')
                    ->label(__('company.parent_company_id'))
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

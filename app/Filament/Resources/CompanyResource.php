<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Filament\Resources\CompanyResource\RelationManagers\AccountsRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\UsersRelationManager;
use App\Models\Company;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Settings::class;

    public static function getModelLabel(): string
    {
        return __('company.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('company.plural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('company.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->label(__('company.address'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('tax_id')
                    ->label(__('company.tax_id'))
                    ->maxLength(255),
                Forms\Components\Select::make('currency_id')
                    ->label(__('company.currency_id'))
                    ->relationship('currency', 'name')
                    ->required()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('code')
                            ->label(__('currency.code'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label(__('currency.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('symbol')
                            ->label(__('currency.symbol'))
                            ->required()
                            ->maxLength(5),
                        Forms\Components\TextInput::make('exchange_rate')
                            ->label(__('currency.exchange_rate'))
                            ->required()
                            ->numeric()
                            ->default(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('currency.is_active'))
                            ->required()
                            ->default(true),
                    ])
                    ->createOptionModalHeading(__('common.modal_title_create_currency'))
                    ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                        return $action
                            ->modalWidth('lg');
                    }),
                Forms\Components\TextInput::make('fiscal_country')
                    ->label(__('company.fiscal_country'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('parent_company_id')
                    ->label(__('company.parent_company_id'))
                    ->relationship('parentCompany', 'name')
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label(__('company.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->label(__('company.address'))
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('tax_id')
                            ->label(__('company.tax_id'))
                            ->maxLength(255),
                        Forms\Components\Select::make('currency_id')
                            ->label(__('company.currency_id'))
                            ->relationship('currency', 'name')
                            ->required(),
                        Forms\Components\TextInput::make('fiscal_country')
                            ->label(__('company.fiscal_country'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->createOptionModalHeading(__('common.modal_title_create_company'))
                    ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                        return $action
                            ->modalWidth('lg');
                    }),
                Forms\Components\Select::make('default_accounts_payable_id')
                    ->relationship('defaultAccountsPayable', 'name')
                    ->searchable()
                    ->label(__('company.default_accounts_payable')),
                Forms\Components\Select::make('default_tax_receivable_id')
                    ->relationship('defaultTaxReceivable', 'name')
                    ->searchable()
                    ->label(__('company.default_tax_receivable')),
                Forms\Components\Select::make('default_purchase_journal_id')
                    ->relationship('defaultPurchaseJournal', 'name')
                    ->searchable()
                    ->label(__('company.default_purchase_journal')),
                Forms\Components\Select::make('default_accounts_receivable_id')
                    ->relationship('defaultAccountsReceivable', 'name')
                    ->searchable()
                    ->label(__('company.default_accounts_receivable')),
                Forms\Components\Select::make('default_sales_discount_account_id')
                    ->relationship('defaultSalesDiscountAccount', 'name')
                    ->searchable()
                    ->label(__('company.default_sales_discount_account')),
                Forms\Components\Select::make('default_tax_account_id')
                    ->relationship('defaultTaxAccount', 'name')
                    ->searchable()
                    ->label(__('company.default_tax_account')),
                Forms\Components\Select::make('default_sales_journal_id')
                    ->relationship('defaultSalesJournal', 'name')
                    ->searchable()
                    ->label(__('company.default_sales_journal')),
                Forms\Components\Select::make('default_depreciation_journal_id')
                    ->relationship('defaultDepreciationJournal', 'name')
                    ->searchable()
                    ->label(__('company.default_depreciation_journal')),
                Forms\Components\Select::make('default_bank_account_id')
                    ->relationship('defaultBankAccount', 'name')
                    ->searchable()
                    ->label(__('company.default_bank_account')),
                Forms\Components\Select::make('default_outstanding_receipts_account_id')
                    ->relationship('defaultOutstandingReceiptsAccount', 'name')
                    ->searchable()
                    ->label(__('company.default_outstanding_receipts_account')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('company.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_id')
                    ->label(__('company.tax_id'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->label(__('company.currency_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fiscal_country')
                    ->label(__('company.fiscal_country'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('parentCompany.name')
                    ->label(__('company.parent_company_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('company.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('company.updated_at'))
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
            RelationManagers\AccountsRelationManager::class,
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}

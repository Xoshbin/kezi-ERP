<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Filament\Resources\CompanyResource\RelationManagers\AccountsRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\UsersRelationManager;
use App\Models\Company;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('tax_id')
                    ->maxLength(255),
                Forms\Components\Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->required(),
                Forms\Components\TextInput::make('fiscal_country')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('parent_company_id')
                    ->relationship('parentCompany', 'name'),
                Forms\Components\Select::make('default_accounts_payable_id')
                    ->relationship('defaultAccountsPayable', 'name')
                    ->searchable()
                    ->label('Default Accounts Payable'),
                Forms\Components\Select::make('default_tax_receivable_id')
                    ->relationship('defaultTaxReceivable', 'name')
                    ->searchable()
                    ->label('Default Tax Receivable'),
                Forms\Components\Select::make('default_purchase_journal_id')
                    ->relationship('defaultPurchaseJournal', 'name')
                    ->searchable()
                    ->label('Default Purchase Journal'),
                Forms\Components\Select::make('default_accounts_receivable_id')
                    ->relationship('defaultAccountsReceivable', 'name')
                    ->searchable()
                    ->label('Default Accounts Receivable'),
                Forms\Components\Select::make('default_sales_discount_account_id')
                    ->relationship('defaultSalesDiscountAccount', 'name')
                    ->searchable()
                    ->label('Default Sales Discount Account'),
                Forms\Components\Select::make('default_tax_account_id')
                    ->relationship('defaultTaxAccount', 'name')
                    ->searchable()
                    ->label('Default Tax Account'),
                Forms\Components\Select::make('default_sales_journal_id')
                    ->relationship('defaultSalesJournal', 'name')
                    ->searchable()
                    ->label('Default Sales Journal'),
                Forms\Components\Select::make('default_depreciation_journal_id')
                    ->relationship('defaultDepreciationJournal', 'name')
                    ->searchable()
                    ->label('Default Depreciation Journal'),
                Forms\Components\Select::make('default_bank_account_id')
                    ->relationship('defaultBankAccount', 'name')
                    ->searchable()
                    ->label('Default Bank Account'),
                Forms\Components\Select::make('default_outstanding_receipts_account_id')
                    ->relationship('defaultOutstandingReceiptsAccount', 'name')
                    ->searchable()
                    ->label('Default Outstanding Receipts Account'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fiscal_country')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parentCompany.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            //
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

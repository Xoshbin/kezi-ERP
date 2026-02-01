<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash;

use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashFundResource\Pages;
use Kezi\Payment\Enums\PettyCash\PettyCashFundStatus;
use Kezi\Payment\Models\PettyCash\PettyCashFund;

class PettyCashFundResource extends Resource
{
    protected static ?string $model = PettyCashFund::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.banking_cash');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::petty_cash.fund.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::petty_cash.fund.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::petty_cash.fund.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::petty_cash.fund.section_details'))
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label(__('accounting::petty_cash.fields.petty_cash_fund')),

                        Select::make('custodian_id')
                            ->relationship('custodian', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label(__('accounting::petty_cash.fields.custodian')),

                        Select::make('account_id')
                            ->relationship('account', 'name', fn ($query) => $query->where('type', 'asset'))
                            ->required()
                            ->label(__('accounting::account.label')),

                        Select::make('bank_account_id')
                            ->relationship('bankAccount', 'name', fn ($query) => $query->where('type', 'asset'))
                            ->required()
                            ->label(__('accounting::journal.fields.bank_account')),

                        Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->required()
                            ->default(1) // IQD
                            ->label(__('accounting::invoice.currency')),

                        TextInput::make('imprest_amount')
                            ->required()
                            ->numeric()
                            ->prefix('IQD')
                            ->label(__('accounting::petty_cash.fields.imprest_amount')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('custodian.name')
                    ->label(__('accounting::petty_cash.fields.custodian'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('imprest_amount')
                    ->money(fn (PettyCashFund $record) => $record->currency->code)
                    ->label(__('accounting::petty_cash.fields.imprest_amount')),

                Tables\Columns\TextColumn::make('current_balance')
                    ->money(fn (PettyCashFund $record) => $record->currency->code)
                    ->label(__('accounting::petty_cash.fields.current_balance'))
                    ->color(fn (PettyCashFund $record) => $record->current_balance->isLessThan($record->imprest_amount->multipliedBy(0.2)) ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PettyCashFundStatus::class),
            ])
            ->actions([
                EditAction::make(),
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
            'index' => Pages\ListPettyCashFunds::route('/'),
            'create' => Pages\CreatePettyCashFund::route('/create'),
            'edit' => Pages\EditPettyCashFund::route('/{record}/edit'),
        ];
    }
}

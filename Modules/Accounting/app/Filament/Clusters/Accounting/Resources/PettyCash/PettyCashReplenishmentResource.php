<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash;

use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashReplenishmentResource\Pages;
use Modules\Payment\Models\PettyCash\PettyCashReplenishment;

class PettyCashReplenishmentResource extends Resource
{
    protected static ?string $model = PettyCashReplenishment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.banking_cash');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::petty_cash.replenishment.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::petty_cash.replenishment.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::petty_cash.replenishment.section_details'))
                    ->schema([
                        Select::make('fund_id')
                            ->relationship('fund', 'name', fn ($query) => $query->where('status', 'active'))
                            ->required()
                            ->label(__('accounting::petty_cash.fields.petty_cash_fund'))
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $fund = \Modules\Payment\Models\PettyCash\PettyCashFund::find($state);
                                    if ($fund) {
                                        $suggested = $fund->imprest_amount->minus($fund->current_balance);
                                        $set('amount', $suggested->getAmount()->toFloat());
                                    }
                                }
                            }),

                        DatePicker::make('replenishment_date')
                            ->required()
                            ->default(now())
                            ->label(__('accounting::petty_cash.fields.replenishment_date')),

                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('IQD')
                            ->label(__('accounting::petty_cash.fields.amount'))
                            ->helperText(__('accounting::petty_cash.helpers.replenishment_amount')),

                        Select::make('payment_method')
                            ->options([
                                'cash' => __('accounting::petty_cash.payment_methods.cash'),
                                'bank_transfer' => __('accounting::petty_cash.payment_methods.bank_transfer'),
                                'cheque' => __('accounting::petty_cash.payment_methods.cheque'),
                            ])
                            ->required()
                            ->default('bank_transfer')
                            ->label(__('accounting::petty_cash.fields.payment_method')),

                        TextInput::make('reference')
                            ->label(__('accounting::petty_cash.fields.reference'))
                            ->helperText(__('accounting::petty_cash.helpers.replenishment_reference')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('replenishment_number')
                    ->label(__('accounting::petty_cash.replenishment.replenishment_number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('replenishment_date')
                    ->label(__('accounting::petty_cash.fields.replenishment_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fund.name')
                    ->label(__('accounting::petty_cash.fields.petty_cash_fund'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('accounting::petty_cash.fields.amount'))
                    ->money(fn (PettyCashReplenishment $record) => $record->fund->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('accounting::petty_cash.fields.payment_method'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('accounting::petty_cash.payment_methods.'.$state)),

                Tables\Columns\TextColumn::make('reference')
                    ->label(__('accounting::petty_cash.fields.reference'))
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fund_id')
                    ->relationship('fund', 'name'),
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
            'index' => Pages\ListPettyCashReplenishments::route('/'),
            'create' => Pages\CreatePettyCashReplenishment::route('/create'),
            'edit' => Pages\EditPettyCashReplenishment::route('/{record}/edit'),
        ];
    }
}

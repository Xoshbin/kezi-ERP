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
        return __('accounting::petty_cash.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::petty_cash.sections.replenishment_details'))
                    ->schema([
                        Select::make('fund_id')
                            ->relationship('fund', 'name', fn ($query) => $query->where('status', 'active'))
                            ->required()
                            ->label(__('accounting::petty_cash.fields.fund'))
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
                            ->helperText(__('accounting::petty_cash.fields.amount_helper')),

                        Select::make('payment_method')
                            ->options([
                                'cash' => __('accounting::petty_cash.fields.payment_methods.cash'),
                                'bank_transfer' => __('accounting::petty_cash.fields.payment_methods.bank_transfer'),
                                'cheque' => __('accounting::petty_cash.fields.payment_methods.cheque'),
                            ])
                            ->required()
                            ->default('bank_transfer')
                            ->label(__('accounting::petty_cash.fields.payment_method')),

                        TextInput::make('reference')
                            ->label(__('accounting::petty_cash.fields.reference'))
                            ->helperText(__('accounting::petty_cash.fields.reference_helper')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('replenishment_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('replenishment_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fund.name')
                    ->label(__('accounting::petty_cash.fields.fund'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money(fn (PettyCashReplenishment $record) => $record->fund->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('reference')
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

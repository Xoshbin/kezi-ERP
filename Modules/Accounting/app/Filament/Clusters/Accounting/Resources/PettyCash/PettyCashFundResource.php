<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash;

use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashFundResource\Pages;
use Modules\Payment\Enums\PettyCash\PettyCashFundStatus;
use Modules\Payment\Models\PettyCash\PettyCashFund;

class PettyCashFundResource extends Resource
{
    protected static ?string $model = PettyCashFund::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Petty Cash';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fund Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Fund Name')
                            ->helperText('e.g., "Main Office Petty Cash"'),

                        Select::make('custodian_id')
                            ->relationship('custodian', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Custodian (Responsible Employee)'),

                        Select::make('account_id')
                            ->relationship('account', 'name', fn ($query) => $query->where('type', 'asset'))
                            ->required()
                            ->label('Petty Cash Account')
                            ->helperText('The GL account for petty cash'),

                        Select::make('bank_account_id')
                            ->relationship('bankAccount', 'name', fn ($query) => $query->where('type', 'asset'))
                            ->required()
                            ->label('Bank Account')
                            ->helperText('Source bank account for replenishments'),

                        Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->required()
                            ->default(1) // IQD
                            ->label('Currency'),

                        TextInput::make('imprest_amount')
                            ->required()
                            ->numeric()
                            ->prefix('IQD')
                            ->label('Imprest Amount')
                            ->helperText('Fixed fund amount'),
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
                    ->label('Custodian')
                    ->searchable(),

                Tables\Columns\TextColumn::make('imprest_amount')
                    ->money(fn (PettyCashFund $record) => $record->currency->code)
                    ->label('Imprest Amount'),

                Tables\Columns\TextColumn::make('current_balance')
                    ->money(fn (PettyCashFund $record) => $record->currency->code)
                    ->label('Current Balance')
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

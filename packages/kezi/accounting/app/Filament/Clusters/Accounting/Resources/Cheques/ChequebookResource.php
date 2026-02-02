<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource\Pages;
use Kezi\Payment\Models\Chequebook;

class ChequebookResource extends Resource
{
    protected static ?string $model = Chequebook::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $cluster = AccountingCluster::class;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()->id);
    }

    public static function getModelLabel(): string
    {
        return __('accounting::cheque.cheque_book_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::cheque.cheque_book_plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Bank & Cash');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::cheque.details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('accounting::cheque.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('bank_account_number')
                            ->label(__('accounting::cheque.bank_account'))
                            ->required()
                            ->maxLength(255),
                        Select::make('journal_id')
                            ->label(__('accounting::journal.journal'))
                            ->options(function () {
                                return \Kezi\Accounting\Models\Journal::where('type', \Kezi\Accounting\Enums\Accounting\JournalType::Bank)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable(),

                        TextInput::make('bank_name')
                            ->required()
                            ->label(__('accounting::cheque.bank_name')),

                        Toggle::make('is_active')
                            ->label(__('accounting::cheque.active'))
                            ->required()
                            ->default(true),
                    ]),
                Section::make(__('accounting::cheque.sequence'))
                    ->schema([
                        TextInput::make('prefix')
                            ->label(__('accounting::cheque.prefix'))
                            ->maxLength(10),
                        TextInput::make('digits')
                            ->numeric()
                            ->default(6)
                            ->label(__('accounting::cheque.digits')),
                        TextInput::make('start_number')
                            ->label(__('accounting::cheque.start_number'))
                            ->numeric()
                            ->required()
                            ->default(1),
                        TextInput::make('end_number')
                            ->label(__('accounting::cheque.end_number'))
                            ->numeric()
                            ->required(),
                        TextInput::make('next_number') // Helper, maybe read only or editable
                            ->label(__('accounting::cheque.next_number'))
                            ->numeric()
                            ->default(1),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('accounting::cheque.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->label(__('accounting::cheque.bank_name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('prefix')
                    ->label(__('accounting::cheque.prefix'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('next_number')
                    ->label(__('accounting::cheque.next_number'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('accounting::cheque.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListChequebooks::route('/'),
            'create' => Pages\CreateChequebook::route('/create'),
            'edit' => Pages\EditChequebook::route('/{record}/edit'),
        ];
    }
}

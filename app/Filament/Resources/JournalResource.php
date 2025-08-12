<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalResource\Pages;
use App\Filament\Resources\JournalResource\RelationManagers;
use App\Models\Journal;
use App\Enums\Accounting\JournalType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Resources\Concerns\Translatable;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JournalResource extends Resource
{
    use Translatable;

    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.core_accounting');
    }

    public static function getModelLabel(): string
    {
        return __('journal.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('journal.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('journal.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->label(__('journal.company'))
                    ->relationship('company', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label(__('journal.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label(__('journal.type'))
                    ->required()
                    ->options(
                        collect(JournalType::cases())
                            ->mapWithKeys(fn (JournalType $type) => [$type->value => $type->label()])
                    )
                    ->searchable(),
                Forms\Components\TextInput::make('short_code')
                    ->label(__('journal.short_code'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('currency_id')
                    ->label(__('journal.currency'))
                    ->relationship('currency', 'name'),

                // ADDED: The missing default account fields
                Forms\Components\Select::make('default_debit_account_id')
                    ->relationship('defaultDebitAccount', 'name')
                    ->searchable()
                    ->label(__('journal.default_debit_account'))
                    ->helperText(__('journal.default_debit_account_helper')),

                Forms\Components\Select::make('default_credit_account_id')
                    ->relationship('defaultCreditAccount', 'name')
                    ->searchable()
                    ->label(__('journal.default_credit_account'))
                    ->helperText(__('journal.default_credit_account_helper')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('journal.company'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('journal.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('journal.type'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('short_code')
                    ->label(__('journal.short_code'))
                    ->searchable(),

                // ADDED: Columns to see the configuration in the list view
                Tables\Columns\TextColumn::make('defaultDebitAccount.name')
                    ->label(__('journal.default_debit_account_short'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('defaultCreditAccount.name')
                    ->label(__('journal.default_credit_account_short'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency.name')
                    ->label(__('journal.currency'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('journal.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('journal.updated_at'))
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
            RelationManagers\JournalEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'edit' => Pages\EditJournal::route('/{record}/edit'),
        ];
    }
}

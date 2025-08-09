<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Account;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Enums\Accounting\AccountType;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Concerns\Translatable;
use App\Filament\Resources\AccountResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AccountResource\RelationManagers;
use App\Filament\Resources\AccountResource\RelationManagers\JournalEntryLinesRelationManager;

class AccountResource extends Resource
{
    use Translatable;

    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.core_accounting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('account.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('account.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->label(__('account.company'))
                    ->relationship('company', 'name')
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->label(__('account.code'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label(__('account.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label(__('account.type'))
                    ->required()
                    ->options(
                        collect(AccountType::cases())
                            ->mapWithKeys(fn (AccountType $type) => [$type->value => $type->label()])
                    )
                    ->searchable(),
                Forms\Components\Toggle::make('is_deprecated')
                    ->label(__('account.is_deprecated'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('account.company'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('account.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('account.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('account.type'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_deprecated')
                    ->label(__('account.is_deprecated'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('account.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('account.updated_at'))
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
            JournalEntryLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}

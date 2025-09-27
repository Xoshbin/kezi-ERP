<?php

namespace Modules\Accounting\Filament\Clusters\Settings\Resources\Accounts;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\Accounting\Models\Account;

class AccountResource extends Resource
{
    use Translatable;

    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getPluralModelLabel(): string
    {
        return __('account.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('account.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('account.basic_information'))
                    ->description(__('account.basic_information_description'))
                    ->schema([
                        TextInput::make('code')
                            ->label(__('account.code'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label(__('account.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label(__('account.type'))
                            ->required()
                            ->options(
                                collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())
                                    ->mapWithKeys(fn (\Modules\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                            )
                            ->searchable(),
                        Toggle::make('is_deprecated')
                            ->label(__('account.is_deprecated'))
                            ->required(),
                        Toggle::make('allow_reconciliation')
                            ->label(__('account.allow_reconciliation'))
                            ->helperText(__('account.allow_reconciliation_help'))
                            ->default(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('account.code'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('account.name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('account.type'))
                    ->searchable(),
                IconColumn::make('is_deprecated')
                    ->label(__('account.is_deprecated'))
                    ->boolean(),
                IconColumn::make('allow_reconciliation')
                    ->label(__('account.allow_reconciliation'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('account.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('account.updated_at'))
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
            JournalEntryLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }
}

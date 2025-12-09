<?php

namespace Modules\Accounting\Filament\Resources\Accounts;

use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Modules\Accounting\Models\Account;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use App\Filament\Clusters\Settings\SettingsCluster;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\Accounting\Filament\Resources\Accounts\Pages\EditAccount;
use Modules\Accounting\Filament\Resources\Accounts\Pages\ListAccounts;
use Modules\Accounting\Filament\Resources\Accounts\Pages\CreateAccount;
use Modules\Accounting\Filament\Resources\Accounts\RelationManagers\JournalEntryLinesRelationManager;

class AccountResource extends Resource
{
    use Translatable;

    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getPluralModelLabel(): string
    {
        return __('accounting::account.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::account.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::account.basic_information'))
                    ->description(__('accounting::account.basic_information_description'))
                    ->schema([
                        TextInput::make('code')
                            ->label(__('accounting::account.code'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label(__('accounting::account.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label(__('accounting::account.type'))
                            ->required()
                            ->options(
                                collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())
                                    ->mapWithKeys(fn(\Modules\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                            )
                            ->searchable(),
                        Toggle::make('is_deprecated')
                            ->label(__('accounting::account.is_deprecated'))
                            ->required(),
                        Toggle::make('allow_reconciliation')
                            ->label(__('accounting::account.allow_reconciliation'))
                            ->helperText(__('accounting::account.allow_reconciliation_help'))
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
                    ->label(__('accounting::account.code'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('accounting::account.name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('accounting::account.type'))
                    ->formatStateUsing(fn($state) => $state instanceof \Modules\Accounting\Enums\Accounting\AccountType ? $state->label() : $state)
                    ->searchable(),
                IconColumn::make('is_deprecated')
                    ->label(__('accounting::account.is_deprecated'))
                    ->boolean(),
                IconColumn::make('allow_reconciliation')
                    ->label(__('accounting::account.allow_reconciliation'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('accounting::account.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('accounting::account.updated_at'))
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

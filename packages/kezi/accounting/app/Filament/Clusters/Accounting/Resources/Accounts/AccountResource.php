<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Accounts;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Accounts\Pages\CreateAccount;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Accounts\Pages\EditAccount;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Accounts\Pages\ListAccounts;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Accounts\RelationManagers\JournalEntryLinesRelationManager;
use Kezi\Accounting\Models\Account;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class AccountResource extends Resource
{
    protected static ?string $cluster = SettingsCluster::class;

    use Translatable;

    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?int $navigationSort = 20;

    public static function getPluralModelLabel(): string
    {
        return __('accounting::account.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::account.plural_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('accounting::navigation.configuration');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make(__('accounting::account.wizard.step_group'))
                        ->description(__('accounting::account.wizard.step_group_description'))
                        ->icon('heroicon-o-folder')
                        ->schema([
                            Select::make('account_group_id')
                                ->label(__('accounting::account.group'))
                                ->relationship('accountGroup', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (! $state) {
                                        return;
                                    }

                                    $group = \Kezi\Accounting\Models\AccountGroup::find($state);
                                    if (! $group) {
                                        return;
                                    }

                                    $service = app(\Kezi\Accounting\Services\AccountGroupService::class);
                                    $nextCode = $service->getNextAccountCode($group);

                                    if ($nextCode) {
                                        $set('code', $nextCode);
                                    }
                                })
                                ->helperText(__('accounting::account.group_help')),

                            \Filament\Forms\Components\Hidden::make('company_id')
                                ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
                        ])
                        ->columns(1),

                    Step::make(__('accounting::account.wizard.step_details'))
                        ->description(__('accounting::account.wizard.step_details_description'))
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            TextInput::make('code')
                                ->label(__('accounting::account.code'))
                                ->required()
                                ->maxLength(255)
                                ->helperText(__('accounting::account.code_help')),
                            TextInput::make('name')
                                ->label(__('accounting::account.name'))
                                ->required()
                                ->maxLength(255),
                            Select::make('type')
                                ->label(__('accounting::account.type'))
                                ->required()
                                ->options(
                                    collect(\Kezi\Accounting\Enums\Accounting\AccountType::cases())
                                        ->mapWithKeys(fn (\Kezi\Accounting\Enums\Accounting\AccountType $type) => [$type->value => $type->label()])
                                )
                                ->searchable(),
                        ])
                        ->columns(2),

                    Step::make(__('accounting::account.wizard.step_options'))
                        ->description(__('accounting::account.wizard.step_options_description'))
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Toggle::make('is_deprecated')
                                ->label(__('accounting::account.is_deprecated'))
                                ->default(false)
                                ->helperText(__('accounting::account.is_deprecated_help')),
                            Toggle::make('allow_reconciliation')
                                ->label(__('accounting::account.allow_reconciliation'))
                                ->helperText(__('accounting::account.allow_reconciliation_help'))
                                ->default(false),
                            Toggle::make('can_create_assets')
                                ->label(__('accounting::account.can_create_assets'))
                                ->helperText(__('accounting::account.can_create_assets_help'))
                                ->default(false)
                                ->visible(fn ($get) => in_array($get('type'), [
                                    \Kezi\Accounting\Enums\Accounting\AccountType::FixedAssets->value,
                                    \Kezi\Accounting\Enums\Accounting\AccountType::Expense->value,
                                ])),
                        ])
                        ->columns(2),
                ])
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
                    ->formatStateUsing(fn ($state) => $state instanceof \Kezi\Accounting\Enums\Accounting\AccountType ? $state->label() : $state)
                    ->searchable(),
                TextColumn::make('accountGroup.name')
                    ->label(__('accounting::account.group'))
                    ->searchable()
                    ->toggleable(),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
    }
}

<?php

namespace App\Filament\Clusters\Accounting\Resources\Journals;

use App\Enums\Accounting\JournalType;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\Journals\Pages\CreateJournal;
use App\Filament\Clusters\Accounting\Resources\Journals\Pages\EditJournal;
use App\Filament\Clusters\Accounting\Resources\Journals\Pages\ListJournals;
use App\Filament\Clusters\Accounting\Resources\Journals\RelationManagers\JournalEntriesRelationManager;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Journal;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class JournalResource extends Resource
{
    use Translatable;

    protected static ?string $model = Journal::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = AccountingCluster::class;

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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('journal.details'))
                ->description(__('journal.details_description'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('journal.name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('type')
                        ->label(__('journal.type'))
                        ->required()
                        ->searchable()
                        ->options(
                            collect(JournalType::cases())
                                ->mapWithKeys(fn (JournalType $type) => [$type->value => $type->label()])
                        )
                        ->searchable(),
                    TextInput::make('short_code')
                        ->label(__('journal.short_code'))
                        ->required()
                        ->maxLength(255),
                    TranslatableSelect::forModel('currency_id', Currency::class, 'name')
                        ->label(__('journal.currency'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('code')
                                ->label(__('currency.code'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name')
                                ->label(__('currency.name'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('symbol')
                                ->label(__('currency.symbol'))
                                ->required()
                                ->maxLength(5),
                            TextInput::make('exchange_rate')
                                ->label(__('currency.exchange_rate'))
                                ->required()
                                ->numeric()
                                ->default(1),
                            Toggle::make('is_active')
                                ->label(__('currency.is_active'))
                                ->required()
                                ->default(true),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_currency'))
                        ->createOptionAction(function (Action $action) {
                            return $action->modalWidth('lg');
                        }),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('journal.default_accounts'))
                ->description(__('journal.default_accounts_description'))
                ->schema([
                    TranslatableSelect::forModel('default_debit_account_id', Account::class, 'name')
                        ->searchable()
                        ->preload()
                        ->helperText(__('journal.default_debit_account_helper')),

                    TranslatableSelect::forModel('default_credit_account_id', Account::class, 'name')
                        ->searchable()
                        ->preload()
                        ->helperText(__('journal.default_credit_account_helper')),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('journal.company'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label(__('journal.name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('journal.type'))
                    ->formatStateUsing(fn ($state) => $state instanceof JournalType ? $state->label() : ($state ? JournalType::from($state)->label() : null))
                    ->badge()
                    ->searchable(),
                TextColumn::make('short_code')
                    ->label(__('journal.short_code'))
                    ->searchable(),

                // Configuration overview
                TextColumn::make('defaultDebitAccount.name')
                    ->label(__('journal.default_debit_account_short'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('defaultCreditAccount.name')
                    ->label(__('journal.default_credit_account_short'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('currency.code')
                    ->label(__('journal.currency'))
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('journal.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('journal.updated_at'))
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
            JournalEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournals::route('/'),
            'create' => CreateJournal::route('/create'),
            'edit' => EditJournal::route('/{record}/edit'),
        ];
    }
}

<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Journals;

use App\Filament\Clusters\Settings\SettingsCluster;
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
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Journals\Pages\CreateJournal;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Journals\Pages\EditJournal;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Journals\Pages\ListJournals;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Journals\RelationManagers\JournalEntriesRelationManager;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class JournalResource extends Resource
{
    use Translatable;

    protected static ?string $model = Journal::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.configuration');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::journal.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::journal.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::journal.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('accounting::journal.details'))
                ->description(__('accounting::journal.details_description'))
                ->schema([
                    \Filament\Forms\Components\Hidden::make('company_id')
                        ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),

                    TextInput::make('name')
                        ->label(__('accounting::journal.name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('type')
                        ->label(__('accounting::journal.type'))
                        ->required()
                        ->searchable()
                        ->options(
                            collect(JournalType::cases())
                                ->mapWithKeys(fn (JournalType $type) => [$type->value => $type->label()])
                        )
                        ->searchable(),
                    TextInput::make('short_code')
                        ->label(__('accounting::journal.short_code'))
                        ->required()
                        ->maxLength(255),
                    TranslatableSelect::forModel('currency_id', Currency::class, 'name')
                        ->label(__('accounting::journal.currency'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('code')
                                ->label(__('accounting::currency.code'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name')
                                ->label(__('accounting::currency.name'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('symbol')
                                ->label(__('accounting::currency.symbol'))
                                ->required()
                                ->maxLength(5),
                            TextInput::make('exchange_rate')
                                ->label(__('accounting::currency.exchange_rate'))
                                ->required()
                                ->numeric()
                                ->default(1),
                            Toggle::make('is_active')
                                ->label(__('accounting::currency.is_active'))
                                ->required()
                                ->default(true),
                        ])
                        ->createOptionModalHeading(__('accounting::common.modal_title_create_currency'))
                        ->createOptionAction(function (Action $action) {
                            return $action->modalWidth('lg');
                        }),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('accounting::journal.default_accounts'))
                ->description(__('accounting::journal.default_accounts_description'))
                ->schema([
                    TranslatableSelect::forModel('default_debit_account_id', Account::class, 'name')
                        ->searchable()
                        ->preload()
                        ->helperText(__('accounting::journal.default_debit_account_helper')),

                    TranslatableSelect::forModel('default_credit_account_id', Account::class, 'name')
                        ->searchable()
                        ->preload()
                        ->helperText(__('accounting::journal.default_credit_account_helper')),
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
                    ->label(__('accounting::journal.company'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label(__('accounting::journal.name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('accounting::journal.type'))
                    ->formatStateUsing(fn ($state) => $state instanceof JournalType ? $state->label() : ($state ? JournalType::from($state)->label() : null))
                    ->badge()
                    ->searchable(),
                TextColumn::make('short_code')
                    ->label(__('accounting::journal.short_code'))
                    ->searchable(),

                // Configuration overview
                TextColumn::make('defaultDebitAccount.name')
                    ->label(__('accounting::journal.default_debit_account_short'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('defaultCreditAccount.name')
                    ->label(__('accounting::journal.default_credit_account_short'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('currency.code')
                    ->label(__('accounting::journal.currency'))
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('accounting::journal.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('accounting::journal.updated_at'))
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
    }
}

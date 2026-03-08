<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Journals;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
use Kezi\Accounting\Filament\Forms\Components\AccountSelectField;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Filament\Forms\Components\CurrencySelectField;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

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
                    CurrencySelectField::make('currency_id')
                        ->required(),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('accounting::journal.default_accounts'))
                ->description(__('accounting::journal.default_accounts_description'))
                ->schema([
                    AccountSelectField::make('default_debit_account_id')
                        ->helperText(__('accounting::journal.default_debit_account_helper')),

                    AccountSelectField::make('default_credit_account_id')
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

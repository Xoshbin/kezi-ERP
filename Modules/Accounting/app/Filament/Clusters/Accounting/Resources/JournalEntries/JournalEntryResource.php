<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries;

use App\Enums\Accounting\JournalType;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\CreateJournalEntry;
use App\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\EditJournalEntry;
use App\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Partner;
use App\Rules\ActiveAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

// Use an alias to avoid conflict with the relationship name

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.core_accounting');
    }

    public static function getModelLabel(): string
    {
        return __('journal_entry.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('journal_entry.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('journal_entry.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('journal_entry.journal_entry'))
                ->schema([
                    TranslatableSelect::forModel('journal_id', Journal::class, 'name')
                        ->label(__('journal_entry.journal'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(Journal::where('type', JournalType::Miscellaneous)->first()?->id)
                        ->columnSpan(2),
                    TranslatableSelect::forModel('currency_id', \Modules\Foundation\Models\Currency::class, 'name')
                        ->label(__('journal_entry.currency'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->default(fn () => (Filament::getTenant() instanceof Company) ? Filament::getTenant()->currency_id : null)
                        ->createOptionForm([
                            TextInput::make('code')->label(__('currency.code'))->required()->maxLength(255),
                            TextInput::make('name')->label(__('currency.name'))->required()->maxLength(255),
                            TextInput::make('symbol')->label(__('currency.symbol'))->required()->maxLength(5),
                            TextInput::make('exchange_rate')->label(__('currency.exchange_rate'))->required()->numeric()->default(1),
                            Toggle::make('is_active')->label(__('currency.is_active'))->required()->default(true),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_currency'))
                        ->createOptionAction(fn (Action $action) => $action->modalWidth('lg'))
                        ->columnSpan(2),
                    DatePicker::make('entry_date')
                        ->label(__('journal_entry.entry_date'))
                        ->required()
                        ->default(now())
                        ->columnSpan(2),

                    TextInput::make('reference')
                        ->label(__('journal_entry.reference'))
                        ->maxLength(255)
                        ->columnSpan(2),
                    Textarea::make('description')
                        ->label(__('journal_entry.description'))
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('journal_entry.lines'))
                ->schema([
                    Repeater::make('lines')
                        ->label(__('journal_entry.lines'))
                        ->disabled(fn (?JournalEntry $record) => $record && $record->is_posted)
                        ->deletable(fn (?JournalEntry $record) => $record === null || ! $record->is_posted)
                        ->table([
                            TableColumn::make(__('journal_entry.account'))->width('20%'),
                            TableColumn::make(__('journal_entry.debit'))->width('15%'),
                            TableColumn::make(__('journal_entry.credit'))->width('15%'),
                            TableColumn::make(__('journal_entry.partner'))->width('20%'),
                            TableColumn::make(__('journal_entry.description'))->width('30%'),
                        ])
                        ->schema([
                            TranslatableSelect::forModel('account_id', \Modules\Accounting\Models\Account::class)
                                ->label(__('journal_entry.account'))
                                ->searchableFields(['name', 'code'])
                                ->searchable()
                                ->preload()
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslatedLabel('name').' ('.$record->code.')')
                                ->rules([new \Modules\Accounting\Rules\ActiveAccount])
                                ->required()
                                ->columnSpan(3),
                            MoneyInput::make('debit')
                                ->label(__('journal_entry.debit'))
                                ->required()
                                ->currencyField('../../company.currency_id')
                                ->live(onBlur: true)
                                ->columnSpan(3),
                            MoneyInput::make('credit')
                                ->label(__('journal_entry.credit'))
                                ->required()
                                ->currencyField('../../company.currency_id')
                                ->live(onBlur: true)
                                ->columnSpan(3),
                            TranslatableSelect::forModel('partner_id', \Modules\Foundation\Models\Partner::class, 'name')
                                ->label(__('journal_entry.partner'))
                                ->searchableFields(['name', 'email', 'contact_person'])
                                ->searchable()
                                ->preload()
                                ->columnSpan(3),
                            TextInput::make('description')
                                ->label(__('journal_entry.description'))
                                ->maxLength(255)
                                ->columnSpan(6),
                        ])
                        ->columns(18)
                        ->columnSpanFull()
                        ->live()
                        ->defaultItems(0)
                        ->afterStateUpdated(function (callable $set, $state) {
                            self::updateTotals($set, $state);
                        }),
                ])
                ->columnSpanFull(),

            Section::make(__('journal_entry.company_currency_totals'))
                ->schema([
                    MoneyInput::make('total_debit')
                        ->label(__('journal_entry.total_debit'))
                        ->currencyField('../../company.currency_id')
                        ->readOnly(),
                    MoneyInput::make('total_credit')
                        ->label(__('journal_entry.total_credit'))
                        ->currencyField('../../company.currency_id')
                        ->readOnly(),
                    MoneyInput::make('balance')
                        ->label(__('journal_entry.balance'))
                        ->currencyField('../../company.currency_id')
                        ->readOnly(),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return Builder<JournalEntry>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company.currency', 'journal', 'currency']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Reference (most important for identification)
                TextColumn::make('reference')
                    ->label(__('journal_entry.reference'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Status (critical for workflow)
                TextColumn::make('is_posted')
                    ->label(__('journal_entry.is_posted'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('enums.journal_entry_state.posted') : __('enums.journal_entry_state.draft'))
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-m-check-circle' : 'heroicon-m-pencil-square')
                    ->sortable(),

                // Entry Date (important for chronological sorting)
                TextColumn::make('entry_date')
                    ->label(__('journal_entry.entry_date'))
                    ->date()
                    ->sortable(),

                // Journal (important for categorization)
                TextColumn::make('journal.name')
                    ->label(__('journal_entry.journal'))
                    ->sortable()
                    ->toggleable(),

                // Total Debit (critical financial information)
                MoneyColumn::make('total_debit')
                    ->label(__('journal_entry.total_debit'))
                    ->sortable()
                    ->weight('bold'),

                // Total Credit (critical financial information)
                MoneyColumn::make('total_credit')
                    ->label(__('journal_entry.total_credit'))
                    ->sortable()
                    ->weight('bold'),

                // Currency (important for multi-currency)
                TextColumn::make('currency.code')
                    ->label(__('journal_entry.currency'))
                    ->badge()
                    ->toggleable(),

                // Company (for multi-company setups)
                TextColumn::make('company.name')
                    ->label(__('journal_entry.company'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('journal_entry.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('journal_entry.updated_at'))
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
            //
        ];
    }

    /**
     * @param  array<int, array{debit?: float|string, credit?: float|string}>  $state
     */
    protected static function updateTotals(callable $set, array $state): void
    {
        $lines = $state;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        $set('total_debit', (float) $totalDebit);
        $set('total_credit', (float) $totalCredit);
        $set('balance', (float) ($totalDebit - $totalCredit));
    }

    public static function getPages(): array
    {
        return [
            // TODO:: in the list page of the journal entries
            // below each journal entry add a dropdown to show the associated lines
            'index' => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'edit' => EditJournalEntry::route('/{record}/edit'),
        ];
    }
}

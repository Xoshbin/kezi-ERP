<?php

namespace App\Filament\Clusters\Accounting\Resources\JournalEntries;

use App\Enums\Accounting\JournalType;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\CreateJournalEntry;
use App\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\EditJournalEntry;
use App\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\JournalEntryResource\Pages;
use App\Filament\Resources\JournalEntryResource\RelationManagers;
use App\Filament\Support\TranslatableSelect;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Rules\ActiveAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

// Use an alias to avoid conflict with the relationship name

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

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
        return $schema
            ->components([
                TranslatableSelect::make('journal_id', \App\Models\Journal::class, __('journal_entry.journal'))
                    ->required()
                    ->default(Journal::where('type', JournalType::Miscellaneous)->first()?->id),
                TranslatableSelect::make('currency_id', \App\Models\Currency::class, __('journal_entry.currency'))
                    ->required()
                    ->live()
                    ->default(fn() => \Filament\Facades\Filament::getTenant()?->currency_id),
                DatePicker::make('entry_date')
                    ->label(__('journal_entry.entry_date'))
                    ->required()
                    ->default(now()),
                TextInput::make('reference')
                    ->label(__('journal_entry.reference'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('journal_entry.description'))
                    ->columnSpanFull(),
                Repeater::make('lines')
                    ->label(__('journal_entry.lines'))
                    ->disabled(fn (?JournalEntry $record) => $record && $record->status !== 'draft')
                    ->deletable(fn (?JournalEntry $record) => !$record || !$record->is_posted)
                    ->schema([
                        TranslatableSelect::withFormatter(
                            'account_id',
                            \App\Models\Account::class,
                            fn($account) => [$account->id => $account->getTranslatedLabel('name') . ' (' . $account->code . ')'],
                            __('journal_entry.account')
                        )
                            ->rules([new ActiveAccount])
                            ->required()
                            ->columnSpan(2),
                        MoneyInput::make('debit')
                            ->label(__('journal_entry.debit'))
                            ->required()
                            ->currencyField('../../currency_id')
                            ->columnSpan(1)
                            ->live(onBlur: true),
                        MoneyInput::make('credit')
                            ->label(__('journal_entry.credit'))
                            ->required()
                            ->currencyField('../../currency_id')
                            ->columnSpan(1)
                            ->live(onBlur: true),
                        TranslatableSelect::standard(
                            'partner_id',
                            \App\Models\Partner::class,
                            ['name', 'email', 'contact_person'],
                            __('journal_entry.partner')
                        )
                            ->columnSpan(2),
                        TranslatableSelect::standard(
                            'analytic_account_id',
                            \App\Models\AnalyticAccount::class,
                            ['name'],
                            __('journal_entry.analytic_account')
                        )
                            ->columnSpan(2),
                        TextInput::make('description')
                            ->label(__('journal_entry.description'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->live()
                    ->defaultItems(0)
                    ->afterStateUpdated(function (callable $set, $state) {
                        self::updateTotals($set, $state);
                    }),
                MoneyInput::make('total_debit')
                    ->label(__('journal_entry.total_debit'))
                    ->currencyField('currency_id')
                    ->readOnly(),
                MoneyInput::make('total_credit')
                    ->label(__('journal_entry.total_credit'))
                    ->currencyField('currency_id')
                    ->readOnly(),
                MoneyInput::make('balance')
                    ->label(__('journal_entry.balance'))
                    ->currencyField('currency_id')
                    ->readOnly(),
            ]);
    }

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
                    ->label(__('journal_entry.status'))
                    ->formatStateUsing(fn(bool $state): string => $state ? __('journal_entry.posted') : __('journal_entry.draft'))
                    ->badge()
                    ->color(fn(bool $state): string => $state ? 'success' : 'warning')
                    ->icon(fn(bool $state): string => $state ? 'heroicon-m-check-circle' : 'heroicon-m-pencil-square')
                    ->sortable(),

                // Entry Date (important for chronological sorting)
                TextColumn::make('entry_date')
                    ->label(__('journal_entry.date'))
                    ->date()
                    ->sortable(),

                // Journal (important for categorization)
                TextColumn::make('journal.name')
                    ->label(__('journal_entry.journal'))
                    ->sortable()
                    ->toggleable(),

                // Total Debit (critical financial information)
                MoneyColumn::make('total_debit')
                    ->label(__('journal_entry.debit'))
                    ->sortable()
                    ->weight('bold'),

                // Total Credit (critical financial information)
                MoneyColumn::make('total_credit')
                    ->label(__('journal_entry.credit'))
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

    protected static function updateTotals(callable $set, array $state): void
    {
        $lines = $state;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit += (float)($line['debit'] ?? 0);
            $totalCredit += (float)($line['credit'] ?? 0);
        }

        $set('total_debit', (float) $totalDebit);
        $set('total_credit', (float) $totalCredit);
        $set('balance', (float) ($totalDebit - $totalCredit));
    }

    public static function getPages(): array
    {
        return [
            //TODO:: in the list page of the journal entries
            //below each journal entry add a dropdown to show the associated lines
            'index' => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'edit' => EditJournalEntry::route('/{record}/edit'),
        ];
    }
}

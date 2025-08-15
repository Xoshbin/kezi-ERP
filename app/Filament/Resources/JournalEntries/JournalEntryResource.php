<?php

namespace App\Filament\Resources\JournalEntries;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry;
use App\Filament\Resources\JournalEntries\Pages\EditJournalEntry;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\JournalEntry;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Tables\Columns\MoneyColumn;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Rules\ActiveAccount;
use App\Filament\Resources\JournalEntryResource\Pages;
use App\Filament\Resources\JournalEntryResource\RelationManagers;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Partner;
use App\Enums\Accounting\JournalType;
use App\Models\AnalyticAccount as AnalyticAccountModel; // Use an alias to avoid conflict with the relationship name

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

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
                Select::make('journal_id')
                    ->label(__('journal_entry.journal'))
                    ->relationship('journal', 'name')
                    ->searchable()
                    ->required()
                    ->default(Journal::where('type', JournalType::Miscellaneous)->first()?->id),
                Select::make('currency_id')
                    ->label(__('journal_entry.currency'))
                    ->relationship('currency', 'name')
                    ->searchable()
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
                        Select::make('account_id')
                            ->label(__('journal_entry.account'))
                            ->options(Account::pluck('name', 'id'))
                            ->searchable()
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
                        Select::make('partner_id')
                            ->label(__('journal_entry.partner'))
                            ->options(Partner::pluck('name', 'id'))
                            ->searchable()
                            ->columnSpan(2),
                        Select::make('analytic_account_id')
                            ->label(__('journal_entry.analytic_account'))
                            ->options(AnalyticAccountModel::pluck('name', 'id'))
                            ->searchable()
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('journal_entry.company'))
                    ->sortable(),
                TextColumn::make('journal.name')
                    ->label(__('journal_entry.journal'))
                    ->sortable(),
                IconColumn::make('is_posted')
                    ->label(__('journal_entry.is_posted'))
                    ->boolean(),
                TextColumn::make('currency.name')
                    ->label(__('journal_entry.currency'))
                    ->sortable(),
                TextColumn::make('entry_date')
                    ->label(__('journal_entry.entry_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('reference')
                    ->label(__('journal_entry.reference'))
                    ->searchable(),
                MoneyColumn::make('total_debit')
                    ->label(__('journal_entry.total_debit'))
                    ->sortable(),
                MoneyColumn::make('total_credit')
                    ->label(__('journal_entry.total_credit'))
                    ->sortable(),
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

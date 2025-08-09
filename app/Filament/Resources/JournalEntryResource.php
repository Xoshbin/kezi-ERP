<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->label(__('journal_entry.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->required()
                    ->live()
                    ->default(Company::first()?->id)
                    ->afterStateUpdated(fn(callable $set, ?string $state) => $set('currency_id', Company::find($state)?->currency_id)),
                Forms\Components\Select::make('journal_id')
                    ->label(__('journal_entry.journal'))
                    ->relationship('journal', 'name')
                    ->searchable()
                    ->required()
                    ->default(Journal::where('type', JournalType::Miscellaneous)->first()?->id),
                Forms\Components\Select::make('currency_id')
                    ->label(__('journal_entry.currency'))
                    ->relationship('currency', 'name')
                    ->searchable()
                    ->required()
                    ->live()
                    ->default(Company::first()?->currency_id),
                Forms\Components\DatePicker::make('entry_date')
                    ->label(__('journal_entry.entry_date'))
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('reference')
                    ->label(__('journal_entry.reference'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('journal_entry.description'))
                    ->columnSpanFull(),
                Repeater::make('lines')
                    ->label(__('journal_entry.lines'))
                    ->schema([
                        Forms\Components\Select::make('account_id')
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
                            ->numeric()
                            ->columnSpan(1)
                            ->live(onBlur: true),
                        MoneyInput::make('credit')
                            ->label(__('journal_entry.credit'))
                            ->required()
                            ->currencyField('../../currency_id')
                            ->numeric()
                            ->columnSpan(1)
                            ->live(onBlur: true),
                        Forms\Components\Select::make('partner_id')
                            ->label(__('journal_entry.partner'))
                            ->options(Partner::pluck('name', 'id'))
                            ->searchable()
                            ->columnSpan(2),
                        Forms\Components\Select::make('analytic_account_id')
                            ->label(__('journal_entry.analytic_account'))
                            ->options(AnalyticAccountModel::pluck('name', 'id'))
                            ->searchable()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('description')
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
                    ->numeric()
                    ->currencyField('currency_id')
                    ->readOnly(),
                MoneyInput::make('total_credit')
                    ->label(__('journal_entry.total_credit'))
                    ->numeric()
                    ->currencyField('currency_id')
                    ->readOnly(),
                MoneyInput::make('balance')
                    ->label(__('journal_entry.balance'))
                    ->numeric()
                    ->currencyField('currency_id')
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('journal_entry.company'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('journal.name')
                    ->label(__('journal_entry.journal'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_posted')
                    ->label(__('journal_entry.is_posted'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->label(__('journal_entry.currency'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('entry_date')
                    ->label(__('journal_entry.entry_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('journal_entry.reference'))
                    ->searchable(),
                MoneyColumn::make('total_debit')
                    ->label(__('journal_entry.total_debit'))
                    ->sortable(),
                MoneyColumn::make('total_credit')
                    ->label(__('journal_entry.total_credit'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('journal_entry.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('journal_entry.updated_at'))
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
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}

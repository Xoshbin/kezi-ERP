<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\BankStatements;

use App\Models\Company;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\Pages\BankReconciliation;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\Pages\CreateBankStatement;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\Pages\EditBankStatement;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\Pages\ListBankStatements;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\RelationManagers\BankStatementLinesRelationManager;
use Kezi\Accounting\Models\BankStatement;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Filament\Forms\Components\MoneyInput;
use Kezi\Foundation\Filament\Tables\Columns\MoneyColumn;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class BankStatementResource extends Resource
{
    protected static ?string $model = BankStatement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Bank & Cash');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::bank_statement.bank_statement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::bank_statement.bank_statements');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::bank_statement.bank_statements');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('accounting::bank_statement.statement_information'))
                ->description(__('accounting::bank_statement.statement_information_description'))
                ->schema([
                    \Filament\Forms\Components\Hidden::make('company_id')
                        ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
                    TranslatableSelect::forModel('currency_id', Currency::class, 'name')
                        ->label(__('accounting::bank_statement.currency'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->columnSpan(2)
                        ->default(function (): ?int {
                            $tenant = Filament::getTenant();

                            return $tenant instanceof Company ? $tenant->currency_id : null;
                        })
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
                        ->createOptionModalHeading(__('accounting::common.modal_title_create_currency')),
                    Select::make('journal_id')
                        ->label(__('accounting::bank_statement.bank_journal'))
                        ->options(function (): array {
                            $tenant = Filament::getTenant();
                            if (! $tenant) {
                                return [];
                            }

                            return Journal::where('type', JournalType::Bank)
                                ->where('company_id', $tenant->getKey())
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->required()
                        ->columnSpan(2)
                        ->rule(function (): Closure {
                            return function (string $attribute, $value, Closure $fail): void {
                                $tenant = Filament::getTenant();
                                if (! $tenant) {
                                    $fail('Company context is required.');

                                    return;
                                }

                                /** @var Journal|null $journal */
                                $journal = Journal::find($value);
                                if (! $journal) {
                                    $fail('The selected bank journal is invalid.');

                                    return;
                                }

                                if ($journal->company_id !== (int) $tenant->getKey()) {
                                    $fail('The selected bank journal is invalid.');

                                    return;
                                }

                                /** @var JournalType $journalType */
                                $journalType = $journal->type;
                                if ($journalType !== JournalType::Bank) {
                                    $fail('The selected bank journal is invalid.');
                                }
                            };
                        }),
                    TextInput::make('reference')
                        ->label(__('accounting::bank_statement.reference'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    DatePicker::make('date')
                        ->label(__('accounting::bank_statement.date'))
                        ->required()
                        ->columnSpan(2),
                    MoneyInput::make('starting_balance')
                        ->label(__('accounting::bank_statement.starting_balance'))
                        ->currencyField('currency_id')
                        ->required()
                        ->columnSpan(2),
                    MoneyInput::make('ending_balance')
                        ->label(__('accounting::bank_statement.ending_balance'))
                        ->currencyField('currency_id')
                        ->required()
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('accounting::bank_statement.statement_lines'))
                ->description(__('accounting::bank_statement.statement_lines_description'))
                ->schema([
                    Repeater::make('bankStatementLines')
                        ->label(__('accounting::bank_statement.statement_lines'))
                        ->table([
                            TableColumn::make(__('accounting::bank_statement.line_date'))->width('15%'),
                            TableColumn::make(__('accounting::bank_statement.description'))->width('20%'),
                            TableColumn::make(__('accounting::bank_statement.partner'))->width('20%'),
                            TableColumn::make(__('accounting::bank_statement.amount'))->width('15%'),
                            TableColumn::make(__('accounting::bank_statement.foreign_currency'))->width('20%'),
                            TableColumn::make(__('accounting::bank_statement.amount_in_foreign_currency'))->width('10%'),
                        ])
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->schema([
                            DatePicker::make('date')
                                ->label(__('accounting::bank_statement.line_date'))
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('description')
                                ->label(__('accounting::bank_statement.description'))
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(4),
                            TranslatableSelect::forModel('partner_id', Partner::class, 'name')
                                ->label(__('accounting::bank_statement.partner'))
                                ->searchable()
                                ->searchableFields(['name', 'email', 'contact_person'])
                                ->preload()
                                ->columnSpan(3),
                            MoneyInput::make('amount')
                                ->label(__('accounting::bank_statement.amount'))
                                ->prefix(function ($get) {
                                    // Try multiple path strategies to get the currency
                                    $currencyId = $get('../../../currency_id')
                                        ?? $get('../../currency_id')
                                        ?? $get('../currency_id')
                                        ?? $get('currency_id');

                                    if ($currencyId) {
                                        $currency = Currency::find($currencyId);

                                        return $currency->code ?? 'IQD';
                                    }

                                    // Fallback to tenant currency
                                    $tenant = Filament::getTenant();

                                    return $tenant->currency->code ?? 'IQD';
                                })
                                ->live()
                                ->reactive()
                                ->required()
                                ->helperText(__('accounting::bank_statement.amount_in_statement_currency'))
                                ->columnSpan(3),
                            TranslatableSelect::forModel('foreign_currency_id', Currency::class, 'name')
                                ->label(__('accounting::bank_statement.foreign_currency'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->options(function ($get) {
                                    $statementCurrencyId = $get('../../../currency_id');

                                    return Currency::where('is_active', true)
                                        ->when($statementCurrencyId, function ($query, $statementCurrencyId) {
                                            return $query->where('id', '!=', $statementCurrencyId);
                                        })
                                        ->get()
                                        ->mapWithKeys(function ($currency) {
                                            $locale = app()->getLocale();
                                            $name = $currency->getTranslation('name', $locale);

                                            return [$currency->id => "{$name} ({$currency->code})"];
                                        });
                                })
                                ->helperText(__('accounting::bank_statement.foreign_currency_help'))
                                ->columnSpan(3),
                            MoneyInput::make('amount_in_foreign_currency')
                                ->label(__('accounting::bank_statement.amount_in_foreign_currency'))
                                ->currencyField('foreign_currency_id')
                                ->visible(fn ($get) => $get('foreign_currency_id'))
                                ->helperText(__('accounting::bank_statement.original_transaction_amount'))
                                ->columnSpan(3),
                        ])
                        ->columns(18)
                        ->addActionLabel(__('accounting::bank_statement.add_transaction_line'))
                        ->defaultItems(1),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Reference (most important for identification)
                TextColumn::make('reference')
                    ->label(__('accounting::bank_statement.reference'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                // Bank Journal (critical for identification)
                TextColumn::make('journal.name')
                    ->label(__('accounting::bank_statement.bank_journal'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Date (important for chronological sorting)
                TextColumn::make('date')
                    ->label(__('accounting::bank_statement.date'))
                    ->date()
                    ->sortable(),

                // Currency (important for multi-currency)
                TextColumn::make('currency.code')
                    ->label(__('accounting::bank_statement.currency'))
                    ->badge()
                    ->sortable(),

                // Starting Balance (critical financial information)
                MoneyColumn::make('starting_balance')
                    ->label(__('accounting::bank_statement.starting_balance'))
                    ->sortable()
                    ->weight('medium'),

                // Ending Balance (critical financial information)
                MoneyColumn::make('ending_balance')
                    ->label(__('accounting::bank_statement.ending_balance'))
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),
                TextColumn::make('created_at')
                    ->label(__('accounting::bank_statement.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('accounting::bank_statement.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('reconcile')
                    ->label(__('accounting::bank_statement.reconcile'))
                    ->icon('heroicon-o-scale')
                    ->color('success')
                    ->url(fn (BankStatement $record): string => static::getUrl('reconcile', ['record' => $record]))
                    ->visible(fn (): bool => Filament::getTenant()->enable_reconciliation ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return Builder<BankStatement>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
    }

    public static function getRelations(): array
    {
        return [
            BankStatementLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankStatements::route('/'),
            'create' => CreateBankStatement::route('/create'),
            'edit' => EditBankStatement::route('/{record}/edit'),
            'reconcile' => BankReconciliation::route('/{record?}/reconcile'),
        ];
    }
}

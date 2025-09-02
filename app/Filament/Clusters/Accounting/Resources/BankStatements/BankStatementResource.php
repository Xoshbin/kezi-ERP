<?php

namespace App\Filament\Clusters\Accounting\Resources\BankStatements;

use App\Models\Currency;
use Filament\Facades\Filament;
use App\Enums\Accounting\JournalType;
use Closure;
use App\Models\Partner;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\BankStatements\Pages\BankReconciliation;
use App\Filament\Clusters\Accounting\Resources\BankStatements\Pages\CreateBankStatement;
use App\Filament\Clusters\Accounting\Resources\BankStatements\Pages\EditBankStatement;
use App\Filament\Clusters\Accounting\Resources\BankStatements\Pages\ListBankStatements;
use App\Filament\Clusters\Accounting\Resources\BankStatements\RelationManagers\BankStatementLinesRelationManager;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Support\TranslatableSelect;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\BankStatement;
use App\Models\Journal;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BankStatementResource extends Resource
{
    protected static ?string $model = BankStatement::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = AccountingCluster::class;
    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.banking_cash');
    }

    public static function getModelLabel(): string
    {
        return __('bank_statement.bank_statement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('bank_statement.bank_statements');
    }

    public static function getNavigationLabel(): string
    {
        return __('bank_statement.bank_statements');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('bank_statement.statement_information'))
                ->description(__('bank_statement.statement_information_description'))
                ->schema([
                    TranslatableSelect::make('currency_id', Currency::class, __('bank_statement.currency'))
                        ->required()
                        ->live()
                        ->columnSpan(2)
                        ->default(fn() => Filament::getTenant()?->currency_id)
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
                        ->createOptionModalHeading(__('common.modal_title_create_currency')),
                    Select::make('journal_id')
                        ->label(__('bank_statement.bank_journal'))
                        ->options(function () {
                            $company = Filament::getTenant();
                            if (!$company) {
                                return [];
                            }
                            return Journal::where('type', JournalType::Bank)
                                ->where('company_id', $company->id)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->required()
                        ->columnSpan(2)
                        ->rule(function () {
                            return function (string $attribute, $value, Closure $fail) {
                                $company = Filament::getTenant();
                                if (!$company) {
                                    $fail('Company context is required.');
                                    return;
                                }

                                $journal = Journal::find($value);
                                if (!$journal || $journal->company_id !== $company->id || $journal->type !== JournalType::Bank) {
                                    $fail('The selected bank journal is invalid.');
                                }
                            };
                        }),
                    TextInput::make('reference')
                        ->label(__('bank_statement.reference'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    DatePicker::make('date')
                        ->label(__('bank_statement.date'))
                        ->required()
                        ->columnSpan(2),
                    MoneyInput::make('starting_balance')
                        ->label(__('bank_statement.starting_balance'))
                        ->currencyField('currency_id')
                        ->required()
                        ->columnSpan(2),
                    MoneyInput::make('ending_balance')
                        ->label(__('bank_statement.ending_balance'))
                        ->currencyField('currency_id')
                        ->required()
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('bank_statement.statement_lines'))
                ->description(__('bank_statement.statement_lines_description'))
                ->schema([
                    Repeater::make('bankStatementLines')
                        ->label(__('bank_statement.statement_lines'))
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->schema([
                            DatePicker::make('date')
                                ->label(__('bank_statement.line_date'))
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('description')
                                ->label(__('bank_statement.description'))
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(4),
                            TranslatableSelect::standard(
                                'partner_id',
                                Partner::class,
                                ['name', 'email', 'contact_person'],
                                __('bank_statement.partner')
                            )
                                ->columnSpan(3),
                            MoneyInput::make('amount')
                                ->label(__('bank_statement.amount'))
                                ->prefix(function ($get) {
                                    // Try multiple path strategies to get the currency
                                    $currencyId = $get('../../../currency_id')
                                        ?? $get('../../currency_id')
                                        ?? $get('../currency_id')
                                        ?? $get('currency_id');

                                    if ($currencyId) {
                                        $currency = Currency::find($currencyId);
                                        return $currency?->code ?? 'IQD';
                                    }

                                    // Fallback to tenant currency
                                    $tenant = Filament::getTenant();
                                    return $tenant?->currency?->code ?? 'IQD';
                                })
                                ->live()
                                ->reactive()
                                ->required()
                                ->helperText(__('bank_statement.amount_in_statement_currency'))
                                ->columnSpan(3),
                            TranslatableSelect::make('foreign_currency_id', Currency::class, __('bank_statement.foreign_currency'))
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
                                ->helperText(__('bank_statement.foreign_currency_help'))
                                ->columnSpan(3),
                            MoneyInput::make('amount_in_foreign_currency')
                                ->label(__('bank_statement.amount_in_foreign_currency'))
                                ->currencyField('foreign_currency_id')
                                ->visible(fn($get) => $get('foreign_currency_id'))
                                ->helperText(__('bank_statement.original_transaction_amount'))
                                ->columnSpan(3),
                        ])
                        ->columns(18)
                        ->addActionLabel(__('bank_statement.add_transaction_line'))
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
                    ->label(__('bank_statement.reference'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                // Bank Journal (critical for identification)
                TextColumn::make('journal.name')
                    ->label(__('bank_statement.bank_journal'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Date (important for chronological sorting)
                TextColumn::make('date')
                    ->label(__('bank_statement.date'))
                    ->date()
                    ->sortable(),

                // Currency (important for multi-currency)
                TextColumn::make('currency.code')
                    ->label(__('bank_statement.currency'))
                    ->badge()
                    ->sortable(),

                // Starting Balance (critical financial information)
                MoneyColumn::make('starting_balance')
                    ->label(__('bank_statement.starting_balance'))
                    ->sortable()
                    ->weight('medium'),

                // Ending Balance (critical financial information)
                MoneyColumn::make('ending_balance')
                    ->label(__('bank_statement.ending_balance'))
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),
                TextColumn::make('created_at')
                    ->label(__('bank_statement.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('bank_statement.updated_at'))
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
                    ->label(__('bank_statement.reconcile'))
                    ->icon('heroicon-o-scale')
                    ->color('success')
                    ->url(fn(BankStatement $record): string => static::getUrl('reconcile', ['record' => $record]))
                    ->visible(fn(): bool => Filament::getTenant()?->enable_reconciliation ?? false)
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Tenancy automatically handles company filtering
        return parent::getEloquentQuery();
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

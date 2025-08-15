<?php

namespace App\Filament\Resources\BankStatements;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\BankStatements\RelationManagers\BankStatementLinesRelationManager;
use App\Filament\Resources\BankStatements\Pages\ListBankStatements;
use App\Filament\Resources\BankStatements\Pages\CreateBankStatement;
use App\Filament\Resources\BankStatements\Pages\ViewBankStatement;
use App\Filament\Resources\BankStatements\Pages\BankReconciliation;
use App\Filament\Resources\BankStatements\Pages\EditBankStatement;
use Filament\Forms;
use Filament\Tables;
use App\Models\Journal;
use Filament\Tables\Table;
use App\Models\BankStatement;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BankStatementResource\Pages;
use App\Filament\Resources\BankStatementResource\RelationManagers;
use App\Models\Partner;
use App\Models\Company;
use App\Filament\Forms\Components\MoneyInput;
use Filament\Infolists;

class BankStatementResource extends Resource
{
    protected static ?string $model = BankStatement::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 2;

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
        $company = Company::first();

        return $schema->components([
            Section::make()
                ->schema([
                    Select::make('company_id')
                        ->relationship('company', 'name')
                        ->label(__('bank_statement.company'))
                        ->required()
                        ->live()
                        ->default($company?->id)
                        ->afterStateUpdated(function (callable $set, $state) {
                            $company = Company::find($state);
                            if ($company) {
                                $set('currency_id', $company->currency_id);
                            }
                        }),
                    Select::make('currency_id')
                        ->relationship('currency', 'name')
                        ->label(__('bank_statement.currency'))
                        ->required()
                        ->live()
                        ->default($company?->currency_id),
                    Select::make('journal_id')
                        ->label(__('bank_statement.bank_journal'))
                        ->options(Journal::where('type', 'Bank')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('reference')
                        ->label(__('bank_statement.reference'))
                        ->required()
                        ->maxLength(255),
                    DatePicker::make('date')
                        ->label(__('bank_statement.date'))
                        ->required(),
                    MoneyInput::make('starting_balance')
                        ->label(__('bank_statement.starting_balance'))
                        ->currencyField('currency_id')
                        ->required(),
                    MoneyInput::make('ending_balance')
                        ->label(__('bank_statement.ending_balance'))
                        ->currencyField('currency_id')
                        ->required(),
                ])->columns(2),

            Section::make(__('bank_statement.transactions'))
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
                            Select::make('partner_id')
                                ->label(__('bank_statement.partner'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array => Partner::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => Partner::find($value)?->name)
                                ->columnSpan(3),
                            MoneyInput::make('amount')
                                ->label(__('bank_statement.amount'))
                                ->currencyField('../../../currency_id')
                                ->required()
                                ->columnSpan(3),
                        ])
                        ->columns(12)
                        ->addActionLabel(__('bank_statement.add_transaction_line'))
                        ->defaultItems(1),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('bank_statement.statement_information'))
                    ->schema([
                        TextEntry::make('reference')
                            ->label(__('bank_statement.reference')),
                        TextEntry::make('date')
                            ->label(__('bank_statement.date'))
                            ->date(),
                        TextEntry::make('company.name')
                            ->label(__('bank_statement.company')),
                        TextEntry::make('currency.name')
                            ->label(__('bank_statement.currency')),
                        TextEntry::make('journal.name')
                            ->label(__('bank_statement.bank_journal')),
                        TextEntry::make('starting_balance')
                            ->label(__('bank_statement.starting_balance'))
                            ->money(fn($record) => $record->currency->code),
                        TextEntry::make('ending_balance')
                            ->label(__('bank_statement.ending_balance'))
                            ->money(fn($record) => $record->currency->code),
                    ])->columns(2),

                Section::make(__('bank_statement.statement_lines'))
                    ->schema([
                        RepeatableEntry::make('bankStatementLines')
                            ->label('')
                            ->schema([
                                TextEntry::make('date')
                                    ->label(__('bank_statement.date'))
                                    ->date(),
                                TextEntry::make('description')
                                    ->label(__('bank_statement.description')),
                                TextEntry::make('partner.name')
                                    ->label(__('bank_statement.partner'))
                                    ->placeholder('—'),
                                TextEntry::make('amount')
                                    ->label(__('bank_statement.amount'))
                                    ->money(fn($record) => $record->bankStatement->currency->code)
                                    ->color(fn($state) => $state->isPositive() ? 'success' : 'danger'),
                                TextEntry::make('is_reconciled')
                                    ->label(__('bank_statement.status'))
                                    ->badge()
                                    ->color(fn($state) => $state ? 'success' : 'warning')
                                    ->formatStateUsing(fn($state) => $state ? 'Reconciled' : 'Pending'),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_id')
                    ->label(__('bank_statement.company_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('journal_id')
                    ->label(__('bank_statement.journal_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reference')
                    ->label(__('bank_statement.reference'))
                    ->searchable(),
                TextColumn::make('date')
                    ->label(__('bank_statement.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('starting_balance')
                    ->label(__('bank_statement.starting_balance'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ending_balance')
                    ->label(__('bank_statement.ending_balance'))
                    ->numeric()
                    ->sortable(),
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
                ViewAction::make(),
                EditAction::make(),
                Action::make('reconcile')
                    ->label(__('bank_statement.reconcile'))
                    ->icon('heroicon-o-scale')
                    ->color('success')
                    ->url(fn(BankStatement $record): string => static::getUrl('reconcile', ['record' => $record]))
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // This line ensures that this resource will ONLY ever see/find
        // bank statements that belong to the logged-in user's company.
        return parent::getEloquentQuery()->where('company_id', auth()->user()->company_id);
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
            'view' => ViewBankStatement::route('/{record}'),
            'reconcile' => BankReconciliation::route('/{record}/reconcile'),
            'edit' => EditBankStatement::route('/{record}/edit'),
        ];
    }
}

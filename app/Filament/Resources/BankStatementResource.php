<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Journal;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\BankStatement;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BankStatementResource\Pages;
use App\Filament\Resources\BankStatementResource\RelationManagers;
use App\Models\Partner;
use App\Models\Company;
use App\Filament\Forms\Components\MoneyInput;
use Filament\Forms\Components\Section;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class BankStatementResource extends Resource
{
    protected static ?string $model = BankStatement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return __('bank_statement.bank_statement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('bank_statement.bank_statements');
    }

    public static function form(Form $form): Form
    {
        $company = Company::first();

        return $form->schema([
            Section::make()
                ->schema([
                    Forms\Components\Select::make('company_id')
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
                    Forms\Components\Select::make('currency_id')
                        ->relationship('currency', 'name')
                        ->label(__('bank_statement.currency'))
                        ->required()
                        ->live()
                        ->default($company?->currency_id),
                    Forms\Components\Select::make('journal_id')
                        ->label(__('bank_statement.bank_journal'))
                        ->options(Journal::where('type', 'Bank')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('reference')
                        ->label(__('bank_statement.reference'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\DatePicker::make('date')
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
                    Forms\Components\Repeater::make('bankStatementLines')
                        ->label(__('bank_statement.statement_lines'))
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->schema([
                            Forms\Components\DatePicker::make('date')
                                ->label(__('bank_statement.line_date'))
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('description')
                                ->label(__('bank_statement.description'))
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(4),
                            Forms\Components\Select::make('partner_id')
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Statement Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Reference'),
                        Infolists\Components\TextEntry::make('date')
                            ->label('Date')
                            ->date(),
                        Infolists\Components\TextEntry::make('company.name')
                            ->label('Company'),
                        Infolists\Components\TextEntry::make('currency.name')
                            ->label('Currency'),
                        Infolists\Components\TextEntry::make('journal.name')
                            ->label('Bank Journal'),
                        Infolists\Components\TextEntry::make('starting_balance')
                            ->label('Starting Balance')
                            ->money(fn($record) => $record->currency->code),
                        Infolists\Components\TextEntry::make('ending_balance')
                            ->label('Ending Balance')
                            ->money(fn($record) => $record->currency->code),
                    ])->columns(2),

                Infolists\Components\Section::make('Bank Statement Lines')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('bankStatementLines')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('date')
                                    ->label('Date')
                                    ->date(),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Description'),
                                Infolists\Components\TextEntry::make('partner.name')
                                    ->label('Partner')
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money(fn($record) => $record->bankStatement->currency->code)
                                    ->color(fn($state) => $state->isPositive() ? 'success' : 'danger'),
                                Infolists\Components\TextEntry::make('is_reconciled')
                                    ->label('Status')
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
                Tables\Columns\TextColumn::make('company_id')
                    ->label(__('bank_statement.company_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('journal_id')
                    ->label(__('bank_statement.journal_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('bank_statement.reference'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('bank_statement.date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('starting_balance')
                    ->label(__('bank_statement.starting_balance'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ending_balance')
                    ->label(__('bank_statement.ending_balance'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('bank_statement.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('bank_statement.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('reconcile')
                    ->label('Reconcile')
                    ->icon('heroicon-o-scale')
                    ->color('success')
                    ->url(fn(BankStatement $record): string => static::getUrl('reconcile', ['record' => $record]))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            RelationManagers\BankStatementLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankStatements::route('/'),
            'create' => Pages\CreateBankStatement::route('/create'),
            'view' => Pages\ViewBankStatement::route('/{record}'),
            'reconcile' => Pages\BankReconciliation::route('/{record}/reconcile'),
            'edit' => Pages\EditBankStatement::route('/{record}/edit'),
        ];
    }
}

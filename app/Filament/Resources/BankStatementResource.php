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
        return $form
            ->schema([
                Forms\Components\Section::make('Statement Details')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->required(),
                        Forms\Components\Select::make('currency_id')
                            ->relationship('currency', 'name')
                            ->required(),
                        Forms\Components\Select::make('journal_id')
                            ->label('Bank Journal')
                            ->options(Journal::where('type', 'Bank')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('reference')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date')
                            ->required(),
                        Forms\Components\TextInput::make('starting_balance')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('ending_balance')
                            ->required()
                            ->numeric(),
                    ])->columns(2),

                Forms\Components\Section::make('Transactions')
                    ->schema([
                        Forms\Components\Repeater::make('bankStatementLines')
                            ->label('Statement Lines')
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('description')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                Forms\Components\Select::make('partner_id')
                                    ->label('Partner')
                                    ->options(Partner::all()->pluck('name', 'id')) // Manually provide the options
                                    ->searchable()
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('amount')
                                    ->required()
                                    ->numeric()
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                            ->addActionLabel('Add Transaction Line')
                            ->defaultItems(1),
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
                Tables\Actions\EditAction::make(),
                Action::make('reconcile')
                    ->label('Reconcile')
                    ->icon('heroicon-o-scale')
                    // This generates the URL to our custom page for the specific record
                    ->url(fn(BankStatement $record): string => static::getUrl('reconcile', ['record' => $record])) // Must use 'record'
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
            'reconcile' => Pages\BankReconciliation::route('/{record}/reconcile'), // Must be {record}
            'edit' => Pages\EditBankStatement::route('/{record}/edit'),
        ];
    }
}

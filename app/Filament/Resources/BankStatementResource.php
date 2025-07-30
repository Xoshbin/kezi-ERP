<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankStatementResource\Pages;
use App\Filament\Resources\BankStatementResource\RelationManagers;
use App\Models\BankStatement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                Forms\Components\TextInput::make('company_id')
                    ->label(__('bank_statement.company_id'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('journal_id')
                    ->label(__('bank_statement.journal_id'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('reference')
                    ->label(__('bank_statement.reference'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date')
                    ->label(__('bank_statement.date'))
                    ->required(),
                Forms\Components\TextInput::make('starting_balance')
                    ->label(__('bank_statement.starting_balance'))
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('ending_balance')
                    ->label(__('bank_statement.ending_balance'))
                    ->required()
                    ->numeric()
                    ->default(0.00),
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
            RelationManagers\BankStatementLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankStatements::route('/'),
            'create' => Pages\CreateBankStatement::route('/create'),
            'edit' => Pages\EditBankStatement::route('/{record}/edit'),
        ];
    }
}

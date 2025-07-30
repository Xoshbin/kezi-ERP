<?php

namespace App\Filament\Resources\BankStatementResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankStatementLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'bankStatementLines';

    protected static ?string $title = 'Bank Statement Lines';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label(__('bank_statement.date'))
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->label(__('bank_statement.description'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('partner_name')
                    ->label(__('bank_statement.partner_name'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label(__('bank_statement.amount'))
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('is_reconciled')
                    ->label(__('bank_statement.is_reconciled'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(null)
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('bank_statement.date'))
                    ->date(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('bank_statement.description')),
                Tables\Columns\TextColumn::make('partner_name')
                    ->label(__('bank_statement.partner_name')),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('bank_statement.amount')),
                Tables\Columns\IconColumn::make('is_reconciled')
                    ->label(__('bank_statement.is_reconciled'))
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

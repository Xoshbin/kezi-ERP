<?php

namespace App\Filament\Resources\JournalEntryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JournalEntryLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('account_id')->relationship('account', 'name')->required(),
                Forms\Components\TextInput::make('debit')->required()->numeric(),
                Forms\Components\TextInput::make('credit')->required()->numeric(),
                Forms\Components\TextInput::make('description')->maxLength(255),
                Forms\Components\Select::make('partner_id')->relationship('partner', 'name'),
                Forms\Components\Select::make('analytic_account_id')->relationship('analyticAccount', 'name'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('account.name'),
                Tables\Columns\TextColumn::make('debit'),
                Tables\Columns\TextColumn::make('credit'),
                Tables\Columns\TextColumn::make('description'),
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

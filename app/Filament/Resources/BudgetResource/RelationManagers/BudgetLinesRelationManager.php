<?php

namespace App\Filament\Resources\BudgetResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BudgetLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetLines';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('analytic_account_id')->relationship('analyticAccount', 'name'),
                Forms\Components\Select::make('account_id')->relationship('account', 'name'),
                Forms\Components\TextInput::make('budgeted_amount')->required()->numeric(),
                Forms\Components\TextInput::make('achieved_amount')->required()->numeric(),
                Forms\Components\TextInput::make('committed_amount')->required()->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('analyticAccount.name'),
                Tables\Columns\TextColumn::make('account.name'),
                Tables\Columns\TextColumn::make('budgeted_amount'),
                Tables\Columns\TextColumn::make('achieved_amount'),
                Tables\Columns\TextColumn::make('committed_amount'),
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

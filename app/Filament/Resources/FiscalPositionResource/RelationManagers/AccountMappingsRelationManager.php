<?php

namespace App\Filament\Resources\FiscalPositionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountMappingsRelationManager extends RelationManager
{
    protected static string $relationship = 'accountMappings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('original_account_id')->relationship('originalAccount', 'name')->required(),
                Forms\Components\Select::make('mapped_account_id')->relationship('mappedAccount', 'name')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('originalAccount.name'),
                Tables\Columns\TextColumn::make('mappedAccount.name'),
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

<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'accounts';

    protected static ?string $title = 'company.accounts.title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label(__('company.accounts.code'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label(__('company.accounts.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('type')
                    ->label(__('company.accounts.type'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_deprecated')
                    ->label(__('company.accounts.is_deprecated'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('company.accounts.code')),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('company.accounts.name')),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('company.accounts.type')),
                Tables\Columns\IconColumn::make('is_deprecated')
                    ->label(__('company.accounts.is_deprecated'))
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

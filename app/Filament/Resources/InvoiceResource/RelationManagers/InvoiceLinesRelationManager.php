<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoiceLines';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')->relationship('product', 'name'),
                Forms\Components\TextInput::make('description')->required()->maxLength(255),
                Forms\Components\TextInput::make('quantity')->required()->numeric(),
                Forms\Components\TextInput::make('unit_price')->required()->numeric(),
                Forms\Components\Select::make('tax_id')->relationship('tax', 'name'),
                Forms\Components\Select::make('income_account_id')->relationship('incomeAccount', 'name')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('product.name'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('unit_price'),
                Tables\Columns\TextColumn::make('tax.name'),
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

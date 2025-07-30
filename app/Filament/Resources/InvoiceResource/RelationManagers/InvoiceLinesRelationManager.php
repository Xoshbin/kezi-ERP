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
                Forms\Components\Select::make('product_id')->relationship('product', 'name')->label(__('invoice.product')),
                Forms\Components\TextInput::make('description')->label(__('invoice.description'))->required()->maxLength(255),
                Forms\Components\TextInput::make('quantity')->label(__('invoice.quantity'))->required()->numeric(),
                Forms\Components\TextInput::make('unit_price')->label(__('invoice.unit_price'))->required()->numeric(),
                Forms\Components\Select::make('tax_id')->relationship('tax', 'name')->label(__('invoice.tax')),
                Forms\Components\Select::make('income_account_id')->relationship('incomeAccount', 'name')->label(__('invoice.income_account'))->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label(__('invoice.product')),
                Tables\Columns\TextColumn::make('description')->label(__('invoice.description')),
                Tables\Columns\TextColumn::make('quantity')->label(__('invoice.quantity')),
                Tables\Columns\TextColumn::make('unit_price')->label(__('invoice.unit_price')),
                Tables\Columns\TextColumn::make('tax.name')->label(__('invoice.tax')),
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

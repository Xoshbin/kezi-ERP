<?php

namespace App\Filament\Resources\VendorBillResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorBillLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')->relationship('product', 'name')->label(__('vendor_bill.product')),
                Forms\Components\TextInput::make('description')->required()->maxLength(255)->label(__('vendor_bill.description')),
                Forms\Components\TextInput::make('quantity')->required()->numeric()->label(__('vendor_bill.quantity')),
                Forms\Components\TextInput::make('unit_price')->required()->numeric()->label(__('vendor_bill.unit_price')),
                Forms\Components\Select::make('tax_id')->relationship('tax', 'name')->label(__('vendor_bill.tax')),
                Forms\Components\Select::make('expense_account_id')->relationship('expenseAccount', 'name')->required()->label(__('vendor_bill.expense_account')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('expenseAccount.name')
                    ->label(__('vendor_bill.account'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('debit')
                    ->label(__('vendor_bill.debit'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit')
                    ->label(__('vendor_bill.credit'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('analyticAccount.name')
                    ->label(__('vendor_bill.analytic_account'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('vendor_bill.description'))
                    ->limit(50),
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

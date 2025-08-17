<?php

namespace App\Filament\Resources\VendorBills\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorBillLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')->relationship('product', 'name')->label(__('vendor_bill.product')),
                TextInput::make('description')->required()->maxLength(255)->label(__('vendor_bill.description')),
                TextInput::make('quantity')->required()->numeric()->label(__('vendor_bill.quantity')),
                TextInput::make('unit_price')->required()->numeric()->label(__('vendor_bill.unit_price')),
                Select::make('tax_id')->relationship('tax', 'name')->label(__('vendor_bill.tax')),
                Select::make('expense_account_id')->relationship('expenseAccount', 'name')->required()->label(__('vendor_bill.expense_account')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('expenseAccount.name')
                    ->label(__('vendor_bill.account'))
                    ->sortable(),
                TextColumn::make('debit')
                    ->label(__('vendor_bill.debit'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('credit')
                    ->label(__('vendor_bill.credit'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('analyticAccount.name')
                    ->label(__('vendor_bill.analytic_account'))
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('vendor_bill.description'))
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

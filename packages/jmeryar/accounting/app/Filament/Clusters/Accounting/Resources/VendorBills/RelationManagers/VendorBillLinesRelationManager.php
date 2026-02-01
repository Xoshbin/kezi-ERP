<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorBillLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')->relationship('product', 'name')->label(__('accounting::bill.product')),
                TextInput::make('description')->required()->maxLength(255)->label(__('accounting::bill.description')),
                TextInput::make('quantity')->required()->numeric()->label(__('accounting::bill.quantity')),
                TextInput::make('unit_price')->required()->numeric()->label(__('accounting::bill.unit_price')),
                Select::make('tax_id')->relationship('tax', 'name')->label(__('accounting::bill.tax')),
                Select::make('expense_account_id')->relationship('expenseAccount', 'name')->required()->label(__('accounting::bill.expense_account')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('expenseAccount.name')
                    ->label(__('accounting::bill.account'))
                    ->sortable(),
                TextColumn::make('debit')
                    ->label(__('accounting::bill.debit'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('credit')
                    ->label(__('accounting::bill.credit'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('analyticAccount.name')
                    ->label(__('accounting::bill.analytic_account'))
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('accounting::bill.description'))
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

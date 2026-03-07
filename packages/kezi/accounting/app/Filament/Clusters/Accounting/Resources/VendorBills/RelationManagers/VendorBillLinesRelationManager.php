<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kezi\Accounting\Enums\Accounting\TaxType;
use Kezi\Accounting\Filament\Forms\Components\AccountSelectField;
use Kezi\Accounting\Filament\Forms\Components\AnalyticAccountSelectField;
use Kezi\Accounting\Filament\Forms\Components\TaxSelectField;
use Kezi\Product\Filament\Forms\Components\ProductSelectField;

/**
 * @extends RelationManager<\Kezi\Purchase\Models\VendorBill>
 */
class VendorBillLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ProductSelectField::make('product_id')->label(__('accounting::bill.product')),
                TextInput::make('description')->required()->maxLength(255)->label(__('accounting::bill.description')),
                TextInput::make('quantity')->required()->numeric()->label(__('accounting::bill.quantity')),
                TextInput::make('unit_price')->required()->numeric()->label(__('accounting::bill.unit_price')),
                TaxSelectField::make('tax_id')
                    ->label(__('accounting::bill.tax'))
                    ->taxFilter([TaxType::Purchase, TaxType::Both])
                    ->createOptionDefaultType(TaxType::Purchase),
                AccountSelectField::make('expense_account_id')
                    ->label(__('accounting::bill.expense_account'))
                    ->required(),
                AnalyticAccountSelectField::make('analytic_account_id')
                    ->label(__('accounting::bill.analytic_account')),
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
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\RelationManagers;

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
use Illuminate\Database\Eloquent\Builder;

class InvoiceLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoiceLines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')->relationship('product', 'name')->label(__('sales::invoice.product')),
                TextInput::make('description')->label(__('sales::invoice.description'))->required()->maxLength(255),
                TextInput::make('quantity')->label(__('sales::invoice.quantity'))->required()->numeric(),
                TextInput::make('unit_price')->label(__('sales::invoice.unit_price'))->required()->numeric(),
                Select::make('tax_id')->relationship('tax', 'name')->label(__('sales::invoice.tax')),
                Select::make('income_account_id')->relationship('incomeAccount', 'name')->label(__('sales::invoice.income_account'))->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['invoice.currency', 'product', 'tax', 'incomeAccount']))
            ->columns([
                TextColumn::make('product.name')->label(__('sales::invoice.product')),
                TextColumn::make('description')->label(__('sales::invoice.description')),
                TextColumn::make('quantity')->label(__('sales::invoice.quantity')),
                TextColumn::make('unit_price')->label(__('sales::invoice.unit_price')),
                TextColumn::make('tax.name')->label(__('sales::invoice.tax')),
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

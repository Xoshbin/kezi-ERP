<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\RelationManagers;

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
use Illuminate\Database\Eloquent\Model;

class InvoiceLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoiceLines';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::invoice.invoice_lines');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::invoice.invoice_line');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::invoice.invoice_lines');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')->relationship('product', 'name')->label(__('accounting::invoice.product')),
                TextInput::make('description')->label(__('accounting::invoice.description'))->required()->maxLength(255),
                TextInput::make('quantity')->label(__('accounting::invoice.quantity'))->required()->numeric(),
                TextInput::make('unit_price')->label(__('accounting::invoice.unit_price'))->required()->numeric(),
                Select::make('tax_id')->relationship('tax', 'name')->label(__('accounting::invoice.tax')),
                Select::make('income_account_id')->relationship('incomeAccount', 'name')->label(__('accounting::invoice.income_account'))->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['invoice.currency', 'product', 'tax', 'incomeAccount']))
            ->columns([
                TextColumn::make('product.name')->label(__('accounting::invoice.product')),
                TextColumn::make('description')->label(__('accounting::invoice.description')),
                TextColumn::make('quantity')->label(__('accounting::invoice.quantity')),
                TextColumn::make('unit_price')->label(__('accounting::invoice.unit_price')),
                TextColumn::make('tax.name')->label(__('accounting::invoice.tax')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label(__('accounting::invoice.add_invoice_line')),
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

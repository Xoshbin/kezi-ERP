<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use App\Models\VendorBill;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBills';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('bill_reference')
                    ->label(__('payment.relation_manager.vendor_bills.form.bill_reference'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('bill_date')
                    ->label(__('payment.relation_manager.vendor_bills.form.bill_date'))
                    ->required(),
                Forms\Components\DatePicker::make('accounting_date')
                    ->label(__('payment.relation_manager.vendor_bills.form.accounting_date'))
                    ->required(),
                Forms\Components\DatePicker::make('due_date')->label(__('payment.relation_manager.vendor_bills.form.due_date')),
                Forms\Components\TextInput::make('status')
                    ->label(__('payment.relation_manager.vendor_bills.form.status'))
                    ->required()
                    ->maxLength(255)
                    ->default(VendorBill::TYPE_DRAFT),
                Forms\Components\TextInput::make('total_amount')
                    ->label(__('payment.relation_manager.vendor_bills.form.total_amount'))
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bill_reference')
            ->columns([
                Tables\Columns\TextColumn::make('bill_reference')->label(__('payment.relation_manager.vendor_bills.column.bill_reference')),
                Tables\Columns\TextColumn::make('bill_date')
                    ->label(__('payment.relation_manager.vendor_bills.column.bill_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('payment.relation_manager.vendor_bills.column.due_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('status')->label(__('payment.relation_manager.vendor_bills.column.status')),
                Tables\Columns\TextColumn::make('total_amount')->label(__('payment.relation_manager.vendor_bills.column.total_amount')),
                Tables\Columns\TextColumn::make('pivot.amount_applied')->label(__('payment.relation_manager.vendor_bills.column.amount_applied')),
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

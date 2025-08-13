<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use App\Enums\Purchases\VendorBillStatus;
use App\Filament\Tables\Columns\MoneyColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

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
                Forms\Components\Select::make('status')
                    ->label(__('payment.relation_manager.vendor_bills.form.status'))
                    ->options([
                        VendorBillStatus::Draft->value => VendorBillStatus::Draft->label(),
                        VendorBillStatus::Posted->value => VendorBillStatus::Posted->label(),
                        VendorBillStatus::Paid->value => VendorBillStatus::Paid->label(),
                        VendorBillStatus::Cancelled->value => VendorBillStatus::Cancelled->label(),
                    ])
                    ->required()
                    ->default(VendorBillStatus::Draft->value),
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
                Tables\Columns\TextColumn::make('bill_reference')
                    ->label(__('payment.relation_manager.vendor_bills.column.bill_reference'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('bill_date')
                    ->label(__('payment.relation_manager.vendor_bills.column.bill_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('payment.relation_manager.vendor_bills.column.due_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('payment.relation_manager.vendor_bills.column.status'))
                    ->formatStateUsing(fn(VendorBillStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn(VendorBillStatus $state): string => match($state) {
                        VendorBillStatus::Draft => 'warning',
                        VendorBillStatus::Posted => 'success',
                        VendorBillStatus::Paid => 'info',
                        VendorBillStatus::Cancelled => 'danger',
                    }),

                MoneyColumn::make('total_amount')
                    ->label(__('payment.relation_manager.vendor_bills.column.total_amount'))
                    ->sortable(),

                MoneyColumn::make('pivot.amount_applied')
                    ->label(__('payment.relation_manager.vendor_bills.column.amount_applied'))
                    ->sortable(),
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

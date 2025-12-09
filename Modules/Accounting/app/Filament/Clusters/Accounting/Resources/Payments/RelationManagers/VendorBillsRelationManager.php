<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Foundation\Filament\Tables\Columns\MoneyColumn;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;

class VendorBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBills';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bill_reference')
                    ->label(__('payment.relation_manager.vendor_bills.form.bill_reference'))
                    ->required()
                    ->maxLength(255),
                DatePicker::make('bill_date')
                    ->label(__('payment.relation_manager.vendor_bills.form.bill_date'))
                    ->required(),
                DatePicker::make('accounting_date')
                    ->label(__('payment.relation_manager.vendor_bills.form.accounting_date'))
                    ->required(),
                DatePicker::make('due_date')->label(__('payment.relation_manager.vendor_bills.form.due_date')),
                Select::make('status')
                    ->label(__('payment.relation_manager.vendor_bills.form.status'))
                    ->options([
                        VendorBillStatus::Draft->value => VendorBillStatus::Draft->label(),
                        VendorBillStatus::Posted->value => VendorBillStatus::Posted->label(),
                        VendorBillStatus::Paid->value => VendorBillStatus::Paid->label(),
                        VendorBillStatus::Cancelled->value => VendorBillStatus::Cancelled->label(),
                    ])
                    ->required()
                    ->default(VendorBillStatus::Draft->value),
                TextInput::make('total_amount')
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
                TextColumn::make('bill_reference')
                    ->label(__('payment.relation_manager.vendor_bills.column.bill_reference'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bill_date')
                    ->label(__('payment.relation_manager.vendor_bills.column.bill_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label(__('payment.relation_manager.vendor_bills.column.due_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('payment.relation_manager.vendor_bills.column.status'))
                    ->formatStateUsing(fn (VendorBillStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (VendorBillStatus $state): string => match ($state) {
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

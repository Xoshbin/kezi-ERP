<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\Sales\InvoiceStatus;
use App\Filament\Tables\Columns\MoneyColumn;
use Filament\Resources\RelationManagers\RelationManager;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->label(__('payment.relation_manager.invoices.form.invoice_number'))
                    ->maxLength(255),
                Forms\Components\DatePicker::make('invoice_date')
                    ->label(__('payment.relation_manager.invoices.form.invoice_date'))
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->label(__('payment.relation_manager.invoices.form.due_date'))
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label(__('payment.relation_manager.invoices.form.status'))
                    ->options([
                        InvoiceStatus::Draft->value => InvoiceStatus::Draft->label(),
                        InvoiceStatus::Posted->value => InvoiceStatus::Posted->label(),
                        InvoiceStatus::Paid->value => InvoiceStatus::Paid->label(),
                        InvoiceStatus::Cancelled->value => InvoiceStatus::Cancelled->label(),
                    ])
                    ->required()
                    ->default(InvoiceStatus::Draft->value),
                Forms\Components\TextInput::make('total_amount')
                    ->label(__('payment.relation_manager.invoices.form.total_amount'))
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label(__('payment.relation_manager.invoices.column.invoice_number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label(__('payment.relation_manager.invoices.column.invoice_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('payment.relation_manager.invoices.column.due_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('payment.relation_manager.invoices.column.status'))
                    ->formatStateUsing(fn(InvoiceStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn(InvoiceStatus $state): string => match($state) {
                        InvoiceStatus::Draft => 'warning',
                        InvoiceStatus::Posted => 'success',
                        InvoiceStatus::Paid => 'info',
                        InvoiceStatus::Cancelled => 'danger',
                    }),

                MoneyColumn::make('total_amount')
                    ->label(__('payment.relation_manager.invoices.column.total_amount'))
                    ->sortable(),

                MoneyColumn::make('pivot.amount_applied')
                    ->label(__('payment.relation_manager.invoices.column.amount_applied'))
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

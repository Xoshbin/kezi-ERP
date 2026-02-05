<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Payments\RelationManagers;

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
use Kezi\Foundation\Filament\Tables\Columns\MoneyColumn;
use Kezi\Sales\Enums\Sales\InvoiceStatus;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->label(__('payment.relation_manager.invoices.form.invoice_number'))
                    ->maxLength(255),
                DatePicker::make('invoice_date')
                    ->label(__('payment.relation_manager.invoices.form.invoice_date'))
                    ->required(),
                DatePicker::make('due_date')
                    ->label(__('payment.relation_manager.invoices.form.due_date'))
                    ->required(),
                Select::make('status')
                    ->label(__('payment.relation_manager.invoices.form.status'))
                    ->options([
                        InvoiceStatus::Draft->value => InvoiceStatus::Draft->label(),
                        InvoiceStatus::Posted->value => InvoiceStatus::Posted->label(),
                        InvoiceStatus::Paid->value => InvoiceStatus::Paid->label(),
                        InvoiceStatus::Cancelled->value => InvoiceStatus::Cancelled->label(),
                    ])
                    ->required()
                    ->default(InvoiceStatus::Draft->value),
                TextInput::make('total_amount')
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
                TextColumn::make('invoice_number')
                    ->label(__('payment.relation_manager.invoices.column.invoice_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label(__('payment.relation_manager.invoices.column.invoice_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label(__('payment.relation_manager.invoices.column.due_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('payment.relation_manager.invoices.column.status'))
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (InvoiceStatus $state): string => match ($state) {
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

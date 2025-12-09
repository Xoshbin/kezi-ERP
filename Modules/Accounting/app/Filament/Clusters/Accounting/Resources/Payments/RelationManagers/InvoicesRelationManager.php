<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\RelationManagers;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Modules\Foundation\Filament\Tables\Columns\MoneyColumn;

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
                    ->formatStateUsing(fn(InvoiceStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn(InvoiceStatus $state): string => match ($state) {
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

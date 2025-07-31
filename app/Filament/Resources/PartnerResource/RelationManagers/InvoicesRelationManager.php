<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'partner.invoices_relation_manager.title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->label(__('partner.invoices_relation_manager.invoice_number'))
                    ->maxLength(255),
                Forms\Components\DatePicker::make('invoice_date')
                    ->label(__('partner.invoices_relation_manager.invoice_date'))
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->label(__('partner.invoices_relation_manager.due_date'))
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->label(__('partner.invoices_relation_manager.status'))
                    ->required()
                    ->maxLength(255)
                    ->default(Invoice::TYPE_DRAFT),
                Forms\Components\TextInput::make('total_amount')
                    ->label(__('partner.invoices_relation_manager.total_amount'))
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
                    ->label(__('partner.invoices_relation_manager.invoice_number')),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label(__('partner.invoices_relation_manager.invoice_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('partner.invoices_relation_manager.due_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('partner.invoices_relation_manager.status')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('partner.invoices_relation_manager.total_amount')),
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

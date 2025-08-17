<?php

namespace App\Filament\Resources\Partners\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('partner.invoices_relation_manager.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->label(__('partner.invoices_relation_manager.invoice_number'))
                    ->maxLength(255),
                DatePicker::make('invoice_date')
                    ->label(__('partner.invoices_relation_manager.invoice_date'))
                    ->required(),
                DatePicker::make('due_date')
                    ->label(__('partner.invoices_relation_manager.due_date'))
                    ->required(),
                TextInput::make('status')
                    ->label(__('partner.invoices_relation_manager.status'))
                    ->required()
                    ->maxLength(255)
                    ->default(Invoice::TYPE_DRAFT),
                TextInput::make('total_amount')
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
                TextColumn::make('invoice_number')
                    ->label(__('partner.invoices_relation_manager.invoice_number')),
                TextColumn::make('invoice_date')
                    ->label(__('partner.invoices_relation_manager.invoice_date'))
                    ->date(),
                TextColumn::make('due_date')
                    ->label(__('partner.invoices_relation_manager.due_date'))
                    ->date(),
                TextColumn::make('status')
                    ->label(__('partner.invoices_relation_manager.status')),
                TextColumn::make('total_amount')
                    ->label(__('partner.invoices_relation_manager.total_amount')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Create action removed - invoices should be created from Invoice resource
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

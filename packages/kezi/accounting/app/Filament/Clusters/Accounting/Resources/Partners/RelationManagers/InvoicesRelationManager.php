<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Kezi\Sales\Enums\Sales\InvoiceStatus;

/**
 * @extends RelationManager<\Kezi\Foundation\Models\Partner>
 */
class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::partner.invoices_relation_manager.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->label(__('accounting::partner.invoices_relation_manager.invoice_number'))
                    ->maxLength(255),
                DatePicker::make('invoice_date')
                    ->label(__('accounting::partner.invoices_relation_manager.invoice_date'))
                    ->required(),
                DatePicker::make('due_date')
                    ->label(__('accounting::partner.invoices_relation_manager.due_date'))
                    ->required(),
                TextInput::make('status')
                    ->label(__('accounting::partner.invoices_relation_manager.status'))
                    ->required()
                    ->maxLength(255)
                    ->default(InvoiceStatus::Draft->value),
                TextInput::make('total_amount')
                    ->label(__('accounting::partner.invoices_relation_manager.total_amount'))
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
                    ->label(__('accounting::partner.invoices_relation_manager.invoice_number')),
                TextColumn::make('invoice_date')
                    ->label(__('accounting::partner.invoices_relation_manager.invoice_date'))
                    ->date(),
                TextColumn::make('due_date')
                    ->label(__('accounting::partner.invoices_relation_manager.due_date'))
                    ->date(),
                TextColumn::make('status')
                    ->label(__('accounting::partner.invoices_relation_manager.status')),
                TextColumn::make('total_amount')
                    ->label(__('accounting::partner.invoices_relation_manager.total_amount')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Create action removed - invoices should be created from Invoice resource
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

<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Partners\RelationManagers;

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
use Modules\Purchase\Enums\Purchases\VendorBillStatus;

class VendorBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBills';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('partner.vendor_bills_relation_manager.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bill_reference')
                    ->label(__('partner.vendor_bills_relation_manager.bill_reference'))
                    ->required()
                    ->maxLength(255),
                DatePicker::make('bill_date')
                    ->label(__('partner.vendor_bills_relation_manager.bill_date'))
                    ->required(),
                DatePicker::make('accounting_date')
                    ->label(__('partner.vendor_bills_relation_manager.accounting_date'))
                    ->required(),
                DatePicker::make('due_date')
                    ->label(__('partner.vendor_bills_relation_manager.due_date')),
                TextInput::make('status')
                    ->label(__('partner.vendor_bills_relation_manager.status'))
                    ->required()
                    ->maxLength(255)
                    ->default(VendorBillStatus::Draft->value),
                TextInput::make('total_amount')
                    ->label(__('partner.vendor_bills_relation_manager.total_amount'))
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
                    ->label(__('partner.vendor_bills_relation_manager.bill_reference')),
                TextColumn::make('bill_date')
                    ->label(__('partner.vendor_bills_relation_manager.bill_date'))
                    ->date(),
                TextColumn::make('due_date')
                    ->label(__('partner.vendor_bills_relation_manager.due_date'))
                    ->date(),
                TextColumn::make('status')
                    ->label(__('partner.vendor_bills_relation_manager.status')),
                TextColumn::make('total_amount')
                    ->label(__('partner.vendor_bills_relation_manager.total_amount')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Create action removed - vendor bills should be created from VendorBill resource
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

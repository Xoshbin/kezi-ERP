<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\VendorBill;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class VendorBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBills';

    protected static ?string $title = null;

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('partner.vendor_bills_relation_manager.title');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('bill_reference')
                    ->label(__('partner.vendor_bills_relation_manager.bill_reference'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('bill_date')
                    ->label(__('partner.vendor_bills_relation_manager.bill_date'))
                    ->required(),
                Forms\Components\DatePicker::make('accounting_date')
                    ->label(__('partner.vendor_bills_relation_manager.accounting_date'))
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->label(__('partner.vendor_bills_relation_manager.due_date')),
                Forms\Components\TextInput::make('status')
                    ->label(__('partner.vendor_bills_relation_manager.status'))
                    ->required()
                    ->maxLength(255)
                    ->default(VendorBill::TYPE_DRAFT),
                Forms\Components\TextInput::make('total_amount')
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
                Tables\Columns\TextColumn::make('bill_reference')
                    ->label(__('partner.vendor_bills_relation_manager.bill_reference')),
                Tables\Columns\TextColumn::make('bill_date')
                    ->label(__('partner.vendor_bills_relation_manager.bill_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('partner.vendor_bills_relation_manager.due_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('partner.vendor_bills_relation_manager.status')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('partner.vendor_bills_relation_manager.total_amount')),
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

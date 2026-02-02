<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Kezi\Foundation\Filament\Tables\Columns\MoneyColumn;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;

class VendorBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBills';

    protected static ?string $recordTitleAttribute = 'bill_reference';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('purchase::vendor_bill.plural_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bill_reference')
            ->columns([
                TextColumn::make('reference')
                    ->label(__('purchase::vendor_bill.reference'))
                    ->getStateUsing(function (VendorBill $record): string {
                        if ($record->bill_reference) {
                            return $record->bill_reference;
                        }

                        return 'DRAFT-'.str_pad((string) $record->id, 5, '0', STR_PAD_LEFT);
                    })
                    ->badge()
                    ->color(fn (VendorBill $record): string => $record->bill_reference ? 'success' : 'warning')
                    ->icon(fn (VendorBill $record): string => $record->bill_reference ? 'heroicon-m-check-circle' : 'heroicon-m-pencil-square')
                    ->url(
                        fn (VendorBill $record): string => route('filament.kezi.accounting.resources.vendor-bills.view', [
                            'record' => $record,
                            'tenant' => Filament::getTenant(),
                        ])
                    )
                    ->openUrlInNewTab(),

                TextColumn::make('status')
                    ->badge()
                    ->label(__('purchase::vendor_bill.status'))
                    ->colors([
                        'success' => VendorBillStatus::Posted,
                        'danger' => VendorBillStatus::Cancelled,
                        'warning' => VendorBillStatus::Draft,
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => VendorBillStatus::Posted,
                        'heroicon-m-x-circle' => VendorBillStatus::Cancelled,
                        'heroicon-m-pencil-square' => VendorBillStatus::Draft,
                    ]),

                TextColumn::make('bill_date')
                    ->label(__('purchase::vendor_bill.bill_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label(__('purchase::vendor_bill.due_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('paymentState')
                    ->label(__('purchase::vendor_bill.payment_state'))
                    ->formatStateUsing(fn (\Kezi\Foundation\Enums\Shared\PaymentState $state): string => $state->label())
                    ->badge()
                    ->color(fn (\Kezi\Foundation\Enums\Shared\PaymentState $state): string => $state->color()),

                MoneyColumn::make('total_amount')
                    ->label(__('purchase::vendor_bill.total_amount'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('purchase::vendor_bill.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('purchase::vendor_bill.actions.create_from_purchase_order'))
                    ->icon('heroicon-o-document-plus')
                    ->url(
                        fn (): string => route('filament.kezi.accounting.resources.vendor-bills.create', [
                            'purchase_order_id' => $this->getOwnerRecord()->id,
                            'tenant' => Filament::getTenant(),
                        ])
                    )
                    ->visible(fn () => $this->getOwnerRecord()->status->canCreateBill()),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

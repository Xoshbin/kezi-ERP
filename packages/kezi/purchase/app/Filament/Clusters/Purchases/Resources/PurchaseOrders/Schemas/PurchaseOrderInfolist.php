<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('purchase::purchase_orders.sections.vendor_currency_information'))
                    ->schema([
                        TextEntry::make('vendor.name')
                            ->label(__('purchase::purchase_orders.fields.vendor'))
                            ->columnSpan(2),

                        TextEntry::make('currency.name')
                            ->label(__('purchase::purchase_orders.fields.currency')),

                        TextEntry::make('exchange_rate_at_creation')
                            ->label(__('purchase::purchase_orders.fields.exchange_rate'))
                            ->numeric(decimalPlaces: 6)
                            ->visible(fn ($record) => $record && $record->exchange_rate_at_creation),

                        TextEntry::make('incoterm')
                            ->label(__('purchase::purchase_orders.fields.incoterm'))
                            ->placeholder('—'),

                        TextEntry::make('incoterm_location')
                            ->label(__('purchase::purchase_orders.fields.incoterm_location'))
                            ->placeholder('—'),

                        TextEntry::make('deliveryLocation.name')
                            ->label(__('purchase::purchase_orders.fields.delivery_location'))
                            ->placeholder('—'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make(__('purchase::purchase_orders.sections.order_details'))
                    ->schema([
                        TextEntry::make('po_number')
                            ->label(__('purchase::purchase_orders.fields.po_number'))
                            ->placeholder(__('purchase::purchase_orders.help.po_number')),

                        TextEntry::make('status')
                            ->label(__('purchase::purchase_orders.fields.status'))
                            ->badge()
                            ->color(fn ($state) => match ($state?->value ?? $state) {
                                'draft' => 'gray',
                                'rfq' => 'info',
                                'rfq_sent' => 'info',
                                'sent' => 'warning',
                                'confirmed' => 'success',
                                'to_receive' => 'warning',
                                'partially_received' => 'warning',
                                'fully_received' => 'success',
                                'to_bill' => 'warning',
                                'partially_billed' => 'warning',
                                'fully_billed' => 'success',
                                'done' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('po_date')
                            ->label(__('purchase::purchase_orders.fields.po_date'))
                            ->date(),

                        TextEntry::make('expected_delivery_date')
                            ->label(__('purchase::purchase_orders.fields.expected_delivery_date'))
                            ->date()
                            ->placeholder('—'),

                        TextEntry::make('reference')
                            ->label(__('purchase::purchase_orders.fields.reference'))
                            ->placeholder('—')
                            ->columnSpan(2),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make(__('purchase::purchase_orders.sections.line_items'))
                    ->description(__('purchase::purchase_orders.sections.line_items_description'))
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->label(__('purchase::purchase_orders.fields.lines'))
                            ->schema([
                                Grid::make(7)
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label(__('purchase::purchase_orders.fields.product'))
                                            ->weight('bold'),

                                        TextEntry::make('description')
                                            ->label(__('purchase::purchase_orders.fields.description')),

                                        TextEntry::make('quantity')
                                            ->label(__('purchase::purchase_orders.fields.quantity'))
                                            ->numeric(decimalPlaces: 2),

                                        TextEntry::make('unit_price')
                                            ->label(__('purchase::purchase_orders.fields.unit_price'))
                                            ->money(fn ($record) => $record->purchaseOrder->currency->code),

                                        TextEntry::make('tax.name')
                                            ->label(__('purchase::purchase_orders.fields.tax'))
                                            ->placeholder('—'),

                                        TextEntry::make('expected_delivery_date')
                                            ->label(__('purchase::purchase_orders.fields.expected_delivery_date'))
                                            ->date()
                                            ->placeholder('—'),

                                        TextEntry::make('notes')
                                            ->label(__('purchase::purchase_orders.fields.notes'))
                                            ->placeholder('—'),
                                    ]),
                            ])
                            ->contained(false)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make(__('purchase::purchase_orders.sections.totals'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Document Currency Totals
                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('total_tax')
                                            ->label(__('purchase::purchase_orders.fields.total_tax'))
                                            ->money(fn ($record) => $record->currency->code),

                                        TextEntry::make('total_amount')
                                            ->label(__('purchase::purchase_orders.fields.total_amount'))
                                            ->money(fn ($record) => $record->currency->code)
                                            ->weight('bold')
                                            ->size('lg'),
                                    ])
                                    ->columnSpan(1),

                                // Company Currency Totals (Conditional)
                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('total_tax_company_currency')
                                            ->label(__('purchase::purchase_orders.fields.total_tax_company_currency'))
                                            ->money(fn ($record) => $record->company->currency->code)
                                            ->visible(fn ($record) => $record && $record->exchange_rate_at_creation),

                                        TextEntry::make('total_amount_company_currency')
                                            ->label(__('purchase::purchase_orders.fields.total_amount_company_currency'))
                                            ->money(fn ($record) => $record->company->currency->code)
                                            ->weight('bold')
                                            ->visible(fn ($record) => $record && $record->exchange_rate_at_creation),
                                    ])
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make(__('purchase::purchase_orders.sections.notes'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('notes')
                                    ->label(__('purchase::purchase_orders.fields.notes'))
                                    ->placeholder('—')
                                    ->columnSpanFull(),

                                TextEntry::make('terms_and_conditions')
                                    ->label(__('purchase::purchase_orders.fields.terms_and_conditions'))
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }
}

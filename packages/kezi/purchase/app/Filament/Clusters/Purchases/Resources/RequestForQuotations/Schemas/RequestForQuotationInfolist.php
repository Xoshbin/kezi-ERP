<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Kezi\Foundation\Filament\Helpers\DocumentTotalsHelper;

class RequestForQuotationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('purchase::request_for_quotation.sections.vendor_info'))
                    ->schema([
                        TextEntry::make('vendor.name')
                            ->label(__('purchase::request_for_quotation.fields.vendor'))
                            ->columnSpan(2),

                        TextEntry::make('currency.name')
                            ->label(__('purchase::request_for_quotation.fields.currency')),

                        TextEntry::make('exchange_rate')
                            ->label(__('purchase::request_for_quotation.fields.exchange_rate'))
                            ->numeric(decimalPlaces: 6),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make(__('purchase::request_for_quotation.sections.basic_info'))
                    ->schema([
                        TextEntry::make('rfq_number')
                            ->label(__('purchase::request_for_quotation.fields.rfq_number')),

                        TextEntry::make('status')
                            ->label(__('purchase::request_for_quotation.fields.status'))
                            ->badge(),

                        TextEntry::make('rfq_date')
                            ->label(__('purchase::request_for_quotation.fields.rfq_date'))
                            ->date(),

                        TextEntry::make('valid_until')
                            ->label(__('purchase::request_for_quotation.fields.valid_until'))
                            ->date()
                            ->placeholder('—'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make(__('purchase::request_for_quotation.sections.line_items'))
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->label(__('purchase::request_for_quotation.sections.line_items'))
                            ->schema([
                                Grid::make(6)
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label(__('purchase::request_for_quotation.lines.product'))
                                            ->weight('bold'),

                                        TextEntry::make('description')
                                            ->label(__('purchase::request_for_quotation.lines.description')),

                                        TextEntry::make('quantity')
                                            ->label(__('purchase::request_for_quotation.lines.quantity'))
                                            ->numeric(decimalPlaces: 2),

                                        TextEntry::make('unit')
                                            ->label(__('purchase::request_for_quotation.lines.unit'))
                                            ->placeholder('—'),

                                        TextEntry::make('unit_price')
                                            ->label(__('purchase::request_for_quotation.lines.unit_price'))
                                            ->money(fn ($record) => $record->rfq->currency->code),

                                        TextEntry::make('tax.name')
                                            ->label(__('purchase::request_for_quotation.lines.tax'))
                                            ->placeholder('—'),
                                    ]),
                            ])
                            ->contained(false)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                DocumentTotalsHelper::makeInfolist(
                    translationPrefix: 'purchase::request_for_quotation.fields',
                    totalsLabel: __('purchase::request_for_quotation.sections.totals'),
                    subtotalLabel: __('purchase::request_for_quotation.fields.subtotal'),
                    taxLabel: __('purchase::request_for_quotation.fields.tax_total'),
                    totalLabel: __('purchase::request_for_quotation.fields.total'),
                    companyCurrencyTotalLabel: __('purchase::request_for_quotation.fields.total'),
                    subtotalKey: 'subtotal',
                    taxKey: 'tax_total',
                    totalKey: 'total',
                    subtotalCompanyKey: 'subtotal_company_currency',
                    taxCompanyKey: 'tax_total_company_currency',
                    totalCompanyKey: 'total_company_currency',
                    exchangeRateKey: 'exchange_rate'
                ),

                Section::make(__('purchase::request_for_quotation.sections.notes'))
                    ->schema([
                        TextEntry::make('notes')
                            ->label(__('purchase::request_for_quotation.fields.notes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }
}

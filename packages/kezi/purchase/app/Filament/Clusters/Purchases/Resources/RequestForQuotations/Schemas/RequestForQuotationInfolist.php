<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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

                Section::make(__('purchase::request_for_quotation.sections.totals'))
                    ->schema([
                        \Filament\Schemas\Components\Fieldset::make(__('purchase::request_for_quotation.fields.document_currency'))
                            ->schema([
                                TextEntry::make('subtotal')
                                    ->label(__('purchase::request_for_quotation.fields.subtotal'))
                                    ->money(fn ($record) => $record->currency->code),

                                TextEntry::make('tax_total')
                                    ->label(__('purchase::request_for_quotation.fields.tax_total'))
                                    ->money(fn ($record) => $record->currency->code),

                                TextEntry::make('total')
                                    ->label(__('purchase::request_for_quotation.fields.total'))
                                    ->money(fn ($record) => $record->currency->code)
                                    ->weight('bold')
                                    ->size('lg'),
                            ])->columns(3),

                        \Filament\Schemas\Components\Fieldset::make(__('purchase::request_for_quotation.fields.company_currency'))
                            ->schema([
                                TextEntry::make('subtotal_company_currency')
                                    ->label(__('purchase::request_for_quotation.fields.subtotal'))
                                    ->money(fn ($record) => $record->company->currency->code)
                                    ->visible(fn ($record) => $record && $record->exchange_rate),

                                TextEntry::make('tax_total_company_currency')
                                    ->label(__('purchase::request_for_quotation.fields.tax_total'))
                                    ->money(fn ($record) => $record->company->currency->code)
                                    ->visible(fn ($record) => $record && $record->exchange_rate),

                                TextEntry::make('total_company_currency')
                                    ->label(__('purchase::request_for_quotation.fields.total'))
                                    ->money(fn ($record) => $record->company->currency->code)
                                    ->weight('bold')
                                    ->size('lg')
                                    ->visible(fn ($record) => $record && $record->exchange_rate),
                            ])->columns(3)
                            ->visible(function ($record) {
                                return $record && $record->exchange_rate && $record->currency_id != $record->company->currency_id;
                            }),
                    ])
                    ->columnSpanFull(),

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

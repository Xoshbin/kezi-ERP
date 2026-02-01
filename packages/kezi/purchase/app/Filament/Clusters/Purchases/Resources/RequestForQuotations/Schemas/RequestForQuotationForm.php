<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus;

class RequestForQuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make(__('purchase::request_for_quotation.sections.general'))
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->label(__('purchase::request_for_quotation.fields.vendor'))
                            ->options(fn () => Partner::query()->whereIn('type', [\Kezi\Foundation\Enums\Partners\PartnerType::Vendor, \Kezi\Foundation\Enums\Partners\PartnerType::Both])->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->disabled()
                            ->default(fn () => auth()->user()->current_company_id)
                            ->dehydrated(),
                        Forms\Components\DatePicker::make('rfq_date')
                            ->label(__('purchase::request_for_quotation.fields.rfq_date'))
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('valid_until')
                            ->label(__('purchase::request_for_quotation.fields.valid_until')),
                        Forms\Components\Select::make('currency_id')
                            ->label(__('purchase::request_for_quotation.fields.currency'))
                            ->options(fn () => Currency::all()->pluck('code', 'id'))
                            ->default(fn () => Currency::where('code', 'USD')->first()?->id)
                            ->required(),
                        Forms\Components\TextInput::make('exchange_rate')
                            ->label(__('purchase::request_for_quotation.fields.exchange_rate'))
                            ->numeric()
                            ->default(1.0)
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(RequestForQuotationStatus::class)
                            ->default(RequestForQuotationStatus::Draft)
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make(__('purchase::request_for_quotation.sections.details'))
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label(__('purchase::request_for_quotation.lines.product'))
                                    ->options(fn () => Product::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, $set) => $set('unit_price', Product::find($state)?->average_cost?->getAmount()->toFloat() ?? 0)),
                                Forms\Components\TextInput::make('description')
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                Forms\Components\TextInput::make('unit')
                                    ->label(__('purchase::request_for_quotation.lines.unit')),
                                // Unit Price should be Money input, but for now simple numeric.
                                // It should be editable if we are recording a bid.
                                Forms\Components\TextInput::make('unit_price')
                                    ->label(__('purchase::request_for_quotation.lines.unit_price'))
                                    ->numeric()
                                    ->prefix('$'), // Should be dynamic based on currency
                                Forms\Components\Select::make('tax_id')
                                    ->label(__('purchase::request_for_quotation.lines.tax'))
                                    ->options(fn () => Tax::all()->pluck('name', 'id')),
                            ])
                            ->columns(6)
                            ->defaultItems(1),
                    ]),

                \Filament\Schemas\Components\Section::make(__('purchase::request_for_quotation.sections.notes'))
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ]),
            ]);
    }
}

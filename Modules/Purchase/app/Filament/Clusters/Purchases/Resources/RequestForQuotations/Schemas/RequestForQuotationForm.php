<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\RequestForQuotationStatus;

class RequestForQuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor')
                            ->options(fn () => Partner::query()->whereIn('type', [\Modules\Foundation\Enums\Partners\PartnerType::Vendor, \Modules\Foundation\Enums\Partners\PartnerType::Both])->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->disabled()
                            ->default(fn () => auth()->user()->current_company_id)
                            ->dehydrated(),
                        Forms\Components\DatePicker::make('rfq_date')
                            ->label('RFQ Date')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('valid_until')
                            ->label('Valid Until'),
                        Forms\Components\Select::make('currency_id')
                            ->label('Currency')
                            ->options(fn () => Currency::all()->pluck('code', 'id'))
                            ->default(fn () => Currency::where('code', 'USD')->first()?->id) // Default logic could be better
                            ->required(),
                        Forms\Components\TextInput::make('exchange_rate')
                            ->label('Exchange Rate')
                            ->numeric()
                            ->default(1.0)
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(RequestForQuotationStatus::class)
                            ->default(RequestForQuotationStatus::Draft)
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->options(fn () => Product::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, $set) => $set('unit_price', Product::find($state)?->cost_price?->getAmount()->toFloat() ?? 0)), // Dummy logic
                                Forms\Components\TextInput::make('description')
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                Forms\Components\TextInput::make('unit')
                                    ->label('Unit'),
                                // Unit Price should be Money input, but for now simple numeric.
                                // It should be editable if we are recording a bid.
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->prefix('$'), // Should be dynamic based on currency
                                Forms\Components\Select::make('tax_id')
                                    ->label('Tax')
                                    ->options(fn () => Tax::all()->pluck('name', 'id')),
                            ])
                            ->columns(6)
                            ->defaultItems(1),
                    ]),

                \Filament\Schemas\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ]),
            ]);
    }
}

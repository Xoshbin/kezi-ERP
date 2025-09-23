<?php

namespace App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Schemas;

use App\Enums\Purchases\PurchaseOrderStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('purchase_orders.sections.basic_info'))
                    ->schema([
                        Hidden::make('company_id')
                            ->default(fn() => \Illuminate\Support\Facades\Auth::user()?->company_id),

                        Hidden::make('created_by_user_id')
                            ->default(fn() => \Illuminate\Support\Facades\Auth::id()),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('po_number')
                                    ->label(__('purchase_orders.fields.po_number'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder(__('purchase_orders.help.po_number')),

                                Select::make('status')
                                    ->label(__('purchase_orders.fields.status'))
                                    ->options(PurchaseOrderStatus::class)
                                    ->default(PurchaseOrderStatus::Draft)
                                    ->disabled(fn(?string $operation) => $operation === 'edit')
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('po_date')
                                    ->label(__('purchase_orders.fields.po_date'))
                                    ->default(now())
                                    ->required(),

                                TextInput::make('reference')
                                    ->label(__('purchase_orders.fields.reference'))
                                    ->helperText(__('purchase_orders.help.reference')),
                            ]),
                    ]),

                Section::make(__('purchase_orders.sections.vendor_details'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('vendor_id')
                                    ->label(__('purchase_orders.fields.vendor'))
                                    ->relationship('vendor', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required(),
                                        TextInput::make('email')
                                            ->email(),
                                    ]),

                                Select::make('currency_id')
                                    ->label(__('purchase_orders.fields.currency'))
                                    ->relationship('currency', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => \Illuminate\Support\Facades\Auth::user()?->company?->currency_id)
                                    ->required(),
                            ]),
                    ]),

                Section::make(__('purchase_orders.sections.delivery_info'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('expected_delivery_date')
                                    ->label(__('purchase_orders.fields.expected_delivery_date')),

                                Select::make('delivery_location_id')
                                    ->label(__('purchase_orders.fields.delivery_location'))
                                    ->relationship('deliveryLocation', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

                Section::make(__('purchase_orders.sections.notes'))
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('purchase_orders.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('terms_and_conditions')
                            ->label(__('purchase_orders.fields.terms_and_conditions'))
                            ->helperText(__('purchase_orders.help.terms_and_conditions'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),


            ]);
    }
}

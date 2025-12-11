<?php

namespace Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Schemas;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Enums\Accounting\TaxType;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Filament\Forms\Components\MoneyInput;
use Modules\Product\Models\Product;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Models\SalesOrder;

class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('sales::sales_orders.sections.basic_info'))
                    ->schema([
                        Hidden::make('company_id')
                            ->default(fn () => Auth::user()?->company_id),

                        Hidden::make('created_by_user_id')
                            ->default(fn () => Auth::id()),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('so_number')
                                    ->label(__('sales::sales_orders.fields.so_number'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder(__('sales::sales_orders.help.so_number')),

                                Select::make('status')
                                    ->label(__('sales::sales_orders.fields.status'))
                                    ->options(function (?string $operation, ?SalesOrder $record) {
                                        // In create mode, show all statuses
                                        if ($operation === 'create') {
                                            return SalesOrderStatus::class;
                                        }

                                        // In edit mode, show only valid forward transitions
                                        if ($operation === 'edit' && $record) {
                                            $validTransitions = $record->status->getValidTransitions();
                                            $options = [];
                                            foreach ($validTransitions as $status) {
                                                $options[$status->value] = $status->label();
                                            }

                                            return $options;
                                        }

                                        return SalesOrderStatus::class;
                                    })
                                    ->default(SalesOrderStatus::Draft)
                                    ->disabled(function (?string $operation, ?SalesOrder $record) {
                                        // Always editable in create mode
                                        if ($operation === 'create') {
                                            return false;
                                        }

                                        // In edit mode, allow editing if status allows flexibility
                                        if ($operation === 'edit' && $record) {
                                            // Allow editing for active statuses that might need manual adjustment
                                            return ! in_array($record->status, [
                                                SalesOrderStatus::Draft,
                                                SalesOrderStatus::Sent,
                                                SalesOrderStatus::Confirmed,
                                                SalesOrderStatus::ToDeliver,
                                                SalesOrderStatus::PartiallyDelivered,
                                                SalesOrderStatus::FullyDelivered,
                                                SalesOrderStatus::ToInvoice,
                                                SalesOrderStatus::PartiallyInvoiced,
                                            ]);
                                        }

                                        return true;
                                    })
                                    ->helperText(function (?string $operation, ?SalesOrder $record) {
                                        if ($operation === 'edit' && $record) {
                                            $messages = [];

                                            // Invoice creation status message
                                            if ($record->canCreateInvoice()) {
                                                $messages[] = __('sales::sales_orders.help.status_can_create_invoice');
                                            } else {
                                                $messages[] = __('sales::sales_orders.help.status_cannot_create_invoice');
                                            }

                                            // Delivery status message
                                            if ($record->canDeliverGoods()) {
                                                $messages[] = __('sales::sales_orders.help.status_can_deliver');
                                            } else {
                                                $messages[] = __('sales::sales_orders.help.status_cannot_deliver');
                                            }

                                            return implode(' ', $messages);
                                        }

                                        return null;
                                    })
                                    ->native(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('customer_id')
                                    ->label(__('sales::sales_orders.fields.customer'))
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionAction(
                                        fn (Action $action) => $action
                                            ->modalHeading(__('foundation::partner.create_customer'))
                                            ->modalSubmitActionLabel(__('foundation::partner.create'))
                                            ->modalWidth('lg')
                                    ),

                                Select::make('currency_id')
                                    ->label(__('sales::sales_orders.fields.currency'))
                                    ->relationship('currency', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->default(fn () => Filament::getTenant()?->currency_id),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('so_date')
                                    ->label(__('sales::sales_orders.fields.order_date'))
                                    ->required()
                                    ->default(now())
                                    ->native(false),

                                DatePicker::make('expected_delivery_date')
                                    ->label(__('sales::sales_orders.fields.expected_delivery_date'))
                                    ->native(false),
                            ]),

                        TextInput::make('reference')
                            ->label(__('sales::sales_orders.fields.reference'))
                            ->maxLength(255)
                            ->helperText(__('sales::sales_orders.help.reference')),
                    ]),

                Section::make(__('sales::sales_orders.sections.line_items'))
                    ->schema([
                        DatePicker::make('expiration')
                            ->label(__('sales::sales_orders.fields.expiration')),
                        Repeater::make('lines')
                            ->label(__('sales::sales_orders.fields.lines'))
                            ->schema([
                                Select::make('product_id')
                                    ->label(__('sales::sales_orders.fields.product'))
                                    ->options(Product::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('description', $product->name);
                                                $set('unit_price', $product->sale_price?->getAmount() ?? 0);
                                            }
                                        }
                                    })
                                    ->createOptionAction(
                                        fn (Action $action) => $action
                                            ->modalHeading(__('product::product.create'))
                                            ->modalSubmitActionLabel(__('product::product.create'))
                                            ->modalWidth('lg')
                                    ),

                                TextInput::make('description')
                                    ->label(__('sales::sales_orders.fields.description'))
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('quantity')
                                    ->label(__('sales::sales_orders.fields.quantity'))
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step(0.01),

                                MoneyInput::make('unit_price')
                                    ->label(__('sales::sales_orders.fields.unit_price'))
                                    ->required(),

                                Select::make('tax_id')
                                    ->label(__('sales::sales_orders.fields.tax'))
                                    ->options(Tax::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->options(function () {
                                        return Tax::where('company_id', Filament::getTenant()?->id)
                                            ->where('type', TaxType::Sales)
                                            ->pluck('name', 'id');
                                    }),

                                DatePicker::make('expected_delivery_date')
                                    ->label(__('sales::sales_orders.fields.line_expected_delivery_date'))
                                    ->native(false),

                                Textarea::make('notes')
                                    ->label(__('sales::sales_orders.fields.line_notes'))
                                    ->rows(2),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel(__('sales::sales_orders.actions.add_line'))
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null),
                    ]),

                Section::make(__('sales::sales_orders.sections.additional_info'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('delivery_location_id')
                                    ->label(__('sales::sales_orders.fields.delivery_location'))
                                    ->relationship('deliveryLocation', 'name')
                                    ->searchable()
                                    ->preload(),

                                // Add payment terms, fiscal position, etc. here if needed
                            ]),

                        Textarea::make('notes')
                            ->label(__('sales::sales_orders.fields.notes'))
                            ->rows(3),

                        Textarea::make('terms_and_conditions')
                            ->label(__('sales::sales_orders.fields.terms_and_conditions'))
                            ->rows(3),
                    ])
                    ->collapsible(),
            ]);
    }
}

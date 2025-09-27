<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Schemas;

use App\Enums\Accounting\TaxType;
use App\Enums\Products\ProductType;
use App\Enums\Purchases\PurchaseOrderStatus;
use App\Filament\Forms\Components\MoneyInput;
use App\Models\Account;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Tax;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

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
                                    ->options(function (?string $operation, ?PurchaseOrder $record) {
                                        // In create mode, show all statuses
                                        if ($operation === 'create') {
                                            return PurchaseOrderStatus::class;
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

                                        return PurchaseOrderStatus::class;
                                    })
                                    ->default(PurchaseOrderStatus::Draft)
                                    ->disabled(function (?string $operation, ?PurchaseOrder $record) {
                                        // Always editable in create mode
                                        if ($operation === 'create') {
                                            return false;
                                        }

                                        // In edit mode, allow editing if status allows flexibility
                                        if ($operation === 'edit' && $record) {
                                            // Allow editing for active statuses that might need manual adjustment
                                            return !in_array($record->status, [
                                                PurchaseOrderStatus::Draft,
                                                PurchaseOrderStatus::Sent,
                                                PurchaseOrderStatus::Confirmed,
                                                PurchaseOrderStatus::ToReceive,
                                                PurchaseOrderStatus::PartiallyReceived,
                                                PurchaseOrderStatus::FullyReceived,
                                                PurchaseOrderStatus::ToBill,
                                                PurchaseOrderStatus::PartiallyBilled,
                                            ]);
                                        }

                                        return true;
                                    })
                                    ->helperText(function (?string $operation, ?PurchaseOrder $record) {
                                        if ($operation === 'edit' && $record) {
                                            $messages = [];

                                            // Bill creation status message
                                            if ($record->canCreateBill()) {
                                                $messages[] = __('purchase_orders.help.status_can_create_bill');
                                            } elseif ($record->hasBills()) {
                                                $messages[] = __('purchase_orders.help.status_bills_already_exist');
                                            } else {
                                                $messages[] = __('purchase_orders.help.status_cannot_create_bill');
                                            }

                                            // Forward-only transition message
                                            $messages[] = __('purchase_orders.help.status_forward_only');

                                            return implode(' ', $messages);
                                        }
                                        return null;
                                    })
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

                Section::make(__('purchase_orders.sections.line_items'))
                    ->description(__('purchase_orders.sections.line_items_description'))
                    ->schema([
                        Repeater::make('lines')
                            ->label(__('purchase_orders.fields.lines'))
                            ->table([
                                TableColumn::make(__('purchase_orders.fields.product'))->width('18%'),
                                TableColumn::make(__('purchase_orders.fields.description'))->width('15%'),
                                TableColumn::make(__('purchase_orders.fields.quantity'))->width('8%'),
                                TableColumn::make(__('purchase_orders.fields.unit_price'))->width('12%'),
                                TableColumn::make(__('purchase_orders.fields.tax'))->width('15%'),
                                TableColumn::make(__('purchase_orders.fields.expected_delivery_date'))->width('12%'),
                                TableColumn::make(__('purchase_orders.fields.notes'))->width('20%'),
                            ])
                            ->live()
                            ->reorderable(true)
                            ->minItems(1)
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                static::updateTotals($set, $get);
                            })
                            ->schema([
                                TranslatableSelect::forModel('product_id', Product::class, 'name')
                                    ->label(__('purchase_orders.fields.product'))
                                    ->searchableFields(['name', 'sku', 'description'])
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('description', $product->description ?: $product->name);
                                                // Convert Money object to string for MoneyInput component
                                                $unitPrice = $product->unit_price;
                                                if ($unitPrice instanceof \Brick\Money\Money) {
                                                    $set('unit_price', $unitPrice->getAmount()->__toString());
                                                } else {
                                                    $set('unit_price', $unitPrice);
                                                }
                                            }
                                        }
                                    })
                                    ->createOptionForm([
                                        Hidden::make('company_id')
                                            ->default(fn() => Filament::getTenant()?->getKey()),
                                        TextInput::make('name')
                                            ->label(__('product.name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('sku')
                                            ->label(__('product.sku'))
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('type')
                                            ->label(__('product.type'))
                                            ->required()
                                            ->live()
                                            ->options(
                                                collect(ProductType::cases())
                                                    ->mapWithKeys(fn(ProductType $type) => [$type->value => $type->label()])
                                            ),
                                        Textarea::make('description')
                                            ->label(__('product.description'))
                                            ->rows(3),
                                        Toggle::make('is_active')
                                            ->label(__('product.is_active'))
                                            ->default(true),
                                    ])
                                    ->createOptionModalHeading(__('common.modal_title_create_product'))
                                    ->createOptionAction(function (Action $action) {
                                        return $action->modalWidth('lg');
                                    })
                                    ->columnSpan(3),

                                TextInput::make('description')
                                    ->label(__('purchase_orders.fields.description'))
                                    ->maxLength(255)
                                    ->required()
                                    ->columnSpan(4),

                                TextInput::make('quantity')
                                    ->label(__('purchase_orders.fields.quantity'))
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(2),

                                MoneyInput::make('unit_price')
                                    ->label(__('purchase_orders.fields.unit_price'))
                                    ->currencyField('../../currency_id')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->columnSpan(3),

                                TranslatableSelect::forModel('tax_id', Tax::class, 'name')
                                    ->label(__('purchase_orders.fields.tax'))
                                    ->options(function () {
                                        return Tax::where('company_id', Filament::getTenant()?->getKey())
                                            ->where('is_active', true)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        static::updateTotals($set, $get);
                                    })
                                    ->createOptionForm([
                                        Hidden::make('company_id')
                                            ->default(fn() => Filament::getTenant()?->getKey()),
                                        Select::make('tax_account_id')
                                            ->options(function () {
                                                return Account::where('company_id', Filament::getTenant()?->getKey())
                                                    ->where('is_deprecated', false)
                                                    ->pluck('name', 'id');
                                            })
                                            ->label(__('tax.tax_account'))
                                            ->searchable()
                                            ->required(),
                                        TextInput::make('name')
                                            ->label(__('tax.name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('rate')
                                            ->label(__('tax.rate'))
                                            ->required()
                                            ->numeric(),
                                        Select::make('type')
                                            ->label(__('tax.type'))
                                            ->options(collect(TaxType::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                                            ->required(),
                                        Toggle::make('is_active')
                                            ->label(__('tax.is_active'))
                                            ->default(true),
                                    ])
                                    ->createOptionUsing(function (array $data): int {
                                        $tax = Tax::create($data);
                                        return $tax->getKey();
                                    })
                                    ->createOptionModalHeading(__('common.modal_title_create_tax'))
                                    ->createOptionAction(function (Action $action) {
                                        return $action->modalWidth('lg');
                                    })
                                    ->columnSpan(3),

                                DatePicker::make('expected_delivery_date')
                                    ->label(__('purchase_orders.fields.expected_delivery_date'))
                                    ->columnSpan(3),

                                Textarea::make('notes')
                                    ->label(__('purchase_orders.fields.notes'))
                                    ->rows(2)
                                    ->columnSpan(3),
                            ])
                            ->columns(18),
                    ])->columnSpanFull(),

                Section::make(__('purchase_orders.sections.totals'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                MoneyInput::make('total_tax')
                                    ->label(__('purchase_orders.fields.total_tax'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),

                                MoneyInput::make('total_amount')
                                    ->label(__('purchase_orders.fields.total_amount'))
                                    ->currencyField('currency_id')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

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

    /**
     * Update the purchase order totals based on line items
     */
    public static function updateTotals(callable $set, callable $get): void
    {
        $lines = $get('lines') ?? [];
        $currencyId = $get('currency_id');

        if (!$currencyId || empty($lines)) {
            $set('total_amount', 0);
            $set('total_tax', 0);
            return;
        }

        // Get currency for calculations
        $currency = \App\Models\Currency::find($currencyId);
        if (!$currency) {
            return;
        }

        $totalAmount = 0;
        $totalTax = 0;

        foreach ($lines as $line) {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $taxId = $line['tax_id'] ?? null;

            if ($quantity <= 0 || $unitPrice <= 0) {
                continue;
            }

            // Calculate line subtotal
            $lineSubtotal = $quantity * $unitPrice;

            // Calculate line tax
            $lineTax = 0;
            if ($taxId) {
                $tax = \App\Models\Tax::find($taxId);
                if ($tax) {
                    $lineTax = $lineSubtotal * ($tax->rate / 100);
                }
            }

            // Calculate line total
            $lineTotal = $lineSubtotal + $lineTax;

            $totalAmount += $lineTotal;
            $totalTax += $lineTax;
        }

        // Set the totals
        $set('total_amount', $totalAmount);
        $set('total_tax', $totalTax);
    }
}

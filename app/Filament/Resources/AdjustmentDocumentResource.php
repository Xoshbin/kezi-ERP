<?php
// in app/Filament/Resources/AdjustmentDocumentResource.php

namespace App\Filament\Resources;

use App\Models\Tax;
use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Currency;
use Filament\Forms\Form;
use App\Models\VendorBill;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\AdjustmentDocument;
use Filament\Forms\Components\Repeater;
use App\Models\Account; // ADDED for Repeater schema
use App\Filament\Resources\AdjustmentDocumentResource\Pages;

use App\Rules\NotInLockedPeriod;

class AdjustmentDocumentResource extends Resource
{
    protected static ?string $model = AdjustmentDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    // Localization functions remain the same...
    public static function getLabel(): ?string
    {
        return __('adjustment_document.label');
    }

    public static function getPluralLabel(): ?string
    {
        return __('adjustment_document.plural_label');
    }


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                // ... (The top sections of the form are correct) ...
                Forms\Components\Section::make(__('adjustment_document.section_details'))->schema([
                    Forms\Components\Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('currency_id')->relationship('currency', 'name')->reactive()->required()
                        ->disabled(fn (Get $get): bool => !empty($get('original_invoice_id')) || !empty($get('original_vendor_bill_id'))),
                    Forms\Components\TextInput::make('reference_number')->required()->maxLength(255),
                    Forms\Components\DatePicker::make('date')->required()->rules([new NotInLockedPeriod()]),
                    Forms\Components\Select::make('type')->options(AdjustmentDocument::getTypes())->required(),
                    Forms\Components\Textarea::make('reason')->required()->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make(__('adjustment_document.section_linking'))->schema([
                    Forms\Components\Select::make('document_link_type')
                        ->label(__('adjustment_document.document_to_adjust'))
                        ->options(['invoice' => __('adjustment_document.original_invoice'), 'vendor_bill' => __('adjustment_document.original_vendor_bill')])
                        ->reactive()
                        ->afterStateUpdated(fn (Set $set) => [$set('original_invoice_id', null), $set('original_vendor_bill_id', null)])
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Get $get, Set $set) {
                            if ($get('original_invoice_id')) $set('document_link_type', 'invoice');
                            elseif ($get('original_vendor_bill_id')) $set('document_link_type', 'vendor_bill');
                        }),
                    Forms\Components\Select::make('original_invoice_id')->searchable()->preload()->relationship('originalInvoice', 'invoice_number')->visible(fn (Get $get) => $get('document_link_type') === 'invoice')
                        ->reactive()->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $invoice = Invoice::find($state);
                                $set('currency_id', $invoice?->currency_id);
                            }
                        }),
                    Forms\Components\Select::make('original_vendor_bill_id')->searchable()->preload()->relationship('originalVendorBill', 'bill_reference')->visible(fn (Get $get) => $get('document_link_type') === 'vendor_bill')
                        ->reactive()->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $bill = VendorBill::find($state);
                                $set('currency_id', $bill?->currency_id);
                            }
                        }),
                ]),
                // --- START OF REQUIRED CHANGES ---
                Forms\Components\Section::make(__('adjustment_document.section_lines'))->schema([
                    Repeater::make('lines')->schema([
                        Forms\Components\Select::make('product_id')
                            ->label(__('vendor_bill.product')) // Using existing translation key for consistency
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Product::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('description', $product->name);
                                        $set('unit_price', $product->unit_price->getAmount()->toFloat());
                                        $set('account_id', $product->income_account_id);
                                    }
                                }
                            })->columnSpan(2),
                        Forms\Components\TextInput::make('description')->required()->columnSpan(3),
                        Forms\Components\TextInput::make('quantity')->required()->numeric()->default(1)->reactive()->columnSpan(1),
                        Forms\Components\TextInput::make('unit_price')->required()->numeric()->reactive()->columnSpan(2),
                        Forms\Components\Select::make('tax_id')
                            ->label(__('vendor_bill.tax')) // Using existing translation key for consistency
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Tax::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Tax::find($value)?->name)
                            ->reactive()->columnSpan(2),
                        Forms\Components\Select::make('account_id')
                            ->label(__('vendor_bill.expense_account')) // Using existing translation key for consistency
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Account::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Account::find($value)?->name)
                            ->required()->columnSpan(4),
                    ])->columns(10)->columnSpanFull(),
                ]),
                // --- END OF REQUIRED CHANGES ---
            ])->columnSpan(['lg' => 2]),
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('adjustment_document.section_status'))->schema([
                    Forms\Components\Select::make('status')->options(AdjustmentDocument::getStatuses())->disabled()->dehydrated(false),
                ]),
                Forms\Components\Section::make(__('adjustment_document.section_totals'))->schema([
                    Forms\Components\TextInput::make('total_tax')->numeric()->readOnly()->prefix(fn (Get $get) => Currency::find($get('currency_id'))?->symbol ?? ''),
                    Forms\Components\TextInput::make('total_amount')->numeric()->readOnly()->prefix(fn (Get $get) => Currency::find($get('currency_id'))?->symbol ?? ''),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    // table(), getRelations(), and getPages() methods are unchanged.
    // ...
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('company.name')->sortable(),
                Tables\Columns\TextColumn::make('type')->searchable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('total_amount')->money(fn($record) => $record->currency->code)->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn(string $state): string => match ($state) {
                    'Draft' => 'gray',
                    'Posted' => 'success',
                    default => 'gray',
                })->searchable(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdjustmentDocuments::route('/'),
            'create' => Pages\CreateAdjustmentDocument::route('/create'),
            'edit' => Pages\EditAdjustmentDocument::route('/{record}/edit'),
        ];
    }
}

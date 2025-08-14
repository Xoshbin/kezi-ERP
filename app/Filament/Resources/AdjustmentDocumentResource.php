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
use App\Models\Company;
use Filament\Forms\Form;
use App\Models\VendorBill;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\AdjustmentDocument;
use App\Enums\Adjustments\AdjustmentDocumentType;
use App\Enums\Adjustments\AdjustmentDocumentStatus;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use App\Models\Account; // ADDED for Repeater schema
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\AdjustmentDocumentResource\Pages;
use Illuminate\Database\Eloquent\Builder;
use App\Rules\NotInLockedPeriod;

class AdjustmentDocumentResource extends Resource
{
    protected static ?string $model = AdjustmentDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.core_accounting');
    }

    // Localization functions remain the same...
    public static function getLabel(): ?string
    {
        return __('adjustment_document.label');
    }

    public static function getPluralLabel(): ?string
    {
        return __('adjustment_document.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('adjustment_document.plural_label');
    }


    public static function form(Form $form): Form
    {
        $company = Company::first();

        return $form->schema([
            Forms\Components\Grid::make(['lg' => 3])->schema([
                Forms\Components\Group::make()->schema([
                Section::make(__('adjustment_document.document_information'))
                    ->description(__('adjustment_document.document_information_description'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([

                            Forms\Components\Select::make('currency_id')
                                ->relationship('currency', 'name')
                                ->label(__('adjustment_document.currency'))
                                ->required()
                                ->live()
                                ->default($company?->currency_id)
                                ->disabled(fn (Get $get): bool => !empty($get('original_invoice_id')) || !empty($get('original_vendor_bill_id')))
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('type')
                                ->label(__('adjustment_document.adjustment_type'))
                                ->options(
                                    collect(AdjustmentDocumentType::cases())
                                        ->mapWithKeys(fn (AdjustmentDocumentType $type) => [$type->value => $type->label()])
                                )
                                ->required()
                                ->searchable(),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('reference_number')
                                ->label(__('adjustment_document.reference_number'))
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., ADJ-2024-001'),
                            Forms\Components\DatePicker::make('date')
                                ->label(__('adjustment_document.adjustment_date'))
                                ->required()
                                ->rules([new NotInLockedPeriod($company)])
                                ->default(now())
                                ->native(false),
                        ]),
                        Forms\Components\Textarea::make('reason')
                            ->label(__('adjustment_document.reason_for_adjustment'))
                            ->required()
                            ->placeholder('Describe the reason for this adjustment...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('adjustment_document.link_to_original_document'))
                    ->description(__('adjustment_document.link_to_original_document_description'))
                    ->icon('heroicon-o-link')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('document_link_type')
                            ->label(__('adjustment_document.document_type_to_adjust'))
                            ->options([
                                'invoice' => __('adjustment_document.invoice'),
                                'vendor_bill' => __('adjustment_document.vendor_bill')
                            ])
                            ->reactive()
                            ->afterStateUpdated(fn (Set $set) => [$set('original_invoice_id', null), $set('original_vendor_bill_id', null)])
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Get $get, Set $set) {
                                if ($get('original_invoice_id')) $set('document_link_type', 'invoice');
                                elseif ($get('original_vendor_bill_id')) $set('document_link_type', 'vendor_bill');
                            })
                            ->placeholder('Select document type to link...'),
                        Forms\Components\Select::make('original_invoice_id')
                            ->label('Original Invoice')
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'originalInvoice',
                                'invoice_number',
                                fn($query) => $query->posted()->with('customer')
                            )
                            ->getOptionLabelUsing(function($value): ?string {
                                $invoice = Invoice::posted()->with('customer')->find($value);
                                if (!$invoice) return null;
                                return $invoice->invoice_number . ' - ' . $invoice->customer->name;
                            })
                            ->visible(fn (Get $get) => $get('document_link_type') === 'invoice')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $invoice = Invoice::find($state);
                                    $set('currency_id', $invoice?->currency_id);
                                }
                            })
                            ->placeholder('Search for an invoice...'),
                        Forms\Components\Select::make('original_vendor_bill_id')
                            ->label('Original Vendor Bill')
                            ->searchable()
                            ->preload()
                            ->relationship('originalVendorBill', 'bill_reference', fn($query) => $query->posted())
                            ->visible(fn (Get $get) => $get('document_link_type') === 'vendor_bill')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $bill = VendorBill::find($state);
                                    $set('currency_id', $bill?->currency_id);
                                }
                            })
                            ->placeholder('Search for a vendor bill...'),
                    ]),
                ])->columnSpan(['lg' => 2]),
                Forms\Components\Group::make()->schema([
                    Section::make(__('adjustment_document.document_status'))
                        ->description(__('adjustment_document.document_status_description'))
                        ->icon('heroicon-o-flag')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label(__('adjustment_document.status'))
                                ->options(collect(AdjustmentDocumentStatus::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                                ->disabled()
                                ->dehydrated(false)
                                ->default(AdjustmentDocumentStatus::Draft->value),
                            Forms\Components\Placeholder::make('created_at')
                                ->label(__('adjustment_document.created'))
                                ->content(fn (?AdjustmentDocument $record): string => $record?->created_at?->format('M j, Y g:i A') ?? 'Not saved yet')
                                ->visible(fn (?AdjustmentDocument $record): bool => $record !== null),
                            Forms\Components\Placeholder::make('updated_at')
                                ->label(__('adjustment_document.last_modified'))
                                ->content(fn (?AdjustmentDocument $record): string => $record?->updated_at?->format('M j, Y g:i A') ?? 'Not saved yet')
                                ->visible(fn (?AdjustmentDocument $record): bool => $record !== null),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]),

            // Full-width Line Items Section
            Section::make(__('adjustment_document.line_items'))
                ->description(__('adjustment_document.line_items_description'))
                ->icon('heroicon-o-list-bullet')
                ->schema([
                    Repeater::make('lines')
                        ->label('')
                        ->live()
                        ->reorderable(false)
                        ->minItems(1)
                        ->addActionLabel(__('adjustment_document.add_line'))
                        ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                        ->schema([
                            Forms\Components\Grid::make([
                                'default' => 1,
                                'sm' => 2,
                                'md' => 6,
                                'lg' => 12,
                            ])->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label(__('adjustment_document.product'))
                                    ->searchable()
                                    ->getSearchResultsUsing(fn(string $search): array => Product::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->name)
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('description', $product->name);
                                                $set('unit_price', $product->unit_price);
                                                $set('account_id', $product->income_account_id);
                                            }
                                        }
                                    })
                                    ->placeholder('Select product...')
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'lg' => 2,
                                    ]),
                                Forms\Components\TextInput::make('description')
                                    ->label(__('adjustment_document.description'))
                                    ->maxLength(255)
                                    ->required()
                                    ->placeholder('Item description')
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'lg' => 3,
                                    ]),
                                Forms\Components\TextInput::make('quantity')
                                    ->label(__('adjustment_document.qty'))
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 1,
                                        'lg' => 1,
                                    ]),
                                MoneyInput::make('unit_price')
                                    ->label('Price')
                                    ->currencyField('../../currency_id')
                                    ->required()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 1,
                                        'lg' => 2,
                                    ]),
                                Forms\Components\Select::make('tax_id')
                                    ->label('Tax')
                                    ->searchable()
                                    ->getSearchResultsUsing(fn(string $search): array =>
                                        Tax::whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.' . app()->getLocale() . '"))) LIKE ?', ['%' . strtolower($search) . '%'])
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn($tax) => [$tax->id => $tax->getTranslation('name', app()->getLocale()) ?: $tax->name])
                                            ->toArray()
                                    )
                                    ->getOptionLabelUsing(fn($value): ?string => Tax::find($value)?->getTranslation('name', app()->getLocale()) ?: Tax::find($value)?->name)
                                    ->placeholder('No tax')
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 1,
                                        'lg' => 2,
                                    ]),
                                Forms\Components\Select::make('account_id')
                                    ->label('Account')
                                    ->searchable()
                                    ->getSearchResultsUsing(fn(string $search): array =>
                                        Account::whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.' . app()->getLocale() . '"))) LIKE ?', ['%' . strtolower($search) . '%'])
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn($account) => [$account->id => $account->getTranslation('name', app()->getLocale()) ?: $account->name])
                                            ->toArray()
                                    )
                                    ->getOptionLabelUsing(fn($value): ?string => Account::find($value)?->getTranslation('name', app()->getLocale()) ?: Account::find($value)?->name)
                                    ->required()
                                    ->placeholder('Select account...')
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'lg' => 2,
                                    ]),
                            ])
                        ])
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    // table(), getRelations(), and getPages() methods are unchanged.
    // ...
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable()
                    ->copyMessage('Reference copied!')
                    ->icon('heroicon-o-hashtag'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->searchable()
                    ->badge()
                    ->color(fn (\App\Enums\Adjustments\AdjustmentDocumentType $state): string => match ($state->value) {
                        'credit_note' => 'success',
                        'debit_note' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (\App\Enums\Adjustments\AdjustmentDocumentType $state): string => ucfirst(str_replace('_', ' ', $state->value))),
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money(fn($record) => $record->currency->code)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Posted' => 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Draft' => 'Draft',
                        'Posted' => 'Posted',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('type')
                    ->options(
                        collect(AdjustmentDocumentType::cases())
                            ->mapWithKeys(fn (AdjustmentDocumentType $type) => [$type->value => $type->label()])
                    )
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading(__('filament.empty_states.no_records_found'))
            ->emptyStateDescription(__('filament.empty_states.create_first_record'))
            ->emptyStateIcon('heroicon-o-document-text');
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

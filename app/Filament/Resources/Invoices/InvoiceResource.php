<?php

namespace App\Filament\Resources\Invoices;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use App\Filament\Support\TranslatableSelect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use App\Enums\Partners\PartnerType;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Exception;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Invoices\RelationManagers\InvoiceLinesRelationManager;
use App\Filament\Resources\Invoices\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Invoices\RelationManagers\AdjustmentDocumentsRelationManager;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Forms\Components\MoneyInput;
use App\Models\Tax;
use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;

use App\Enums\Sales\InvoiceStatus;
use App\Enums\Shared\PaymentState;
use Filament\Tables\Table;
use App\Services\InvoiceService;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\InvoiceResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Account;
use App\Models\Product;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Journal;
use App\Models\FiscalPosition;
use App\Rules\NotInLockedPeriod;
use App\Filament\Tables\Columns\MoneyColumn;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.sales_purchases');
    }

    public static function getModelLabel(): string
    {
        return __('invoice.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('invoice.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('invoice.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TranslatableSelect::standard(
                        'customer_id',
                        \App\Models\Partner::class,
                        ['name', 'email', 'contact_person'],
                        __('invoice.customer')
                    )
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label(__('partner.name'))
                                ->required()
                                ->maxLength(255),
                            Select::make('type')
                                ->label(__('partner.type'))
                                ->required()
                                ->options(
                                    collect(PartnerType::cases())
                                        ->mapWithKeys(fn (PartnerType $type) => [$type->value => $type->label()])
                                ),
                            TextInput::make('contact_person')
                                ->label(__('partner.contact_person'))
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label(__('partner.email'))
                                ->email()
                                ->maxLength(255),
                            TextInput::make('phone')
                                ->label(__('partner.phone'))
                                ->maxLength(255),
                            Textarea::make('address')
                                ->label(__('partner.address'))
                                ->columnSpanFull(),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_partner'))
                        ->createOptionAction(function (Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),
                    TranslatableSelect::make('currency_id', \App\Models\Currency::class, __('invoice.currency'))
                        ->required()
                        ->live()
                        ->default(fn() => \Filament\Facades\Filament::getTenant()?->currency_id)
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                            if ($state) {
                                $currency = \App\Models\Currency::find($state);
                                $company = \Filament\Facades\Filament::getTenant();

                                if ($currency && $company && $currency->id !== $company->currency_id) {
                                    // Get latest exchange rate
                                    $latestRate = \App\Models\CurrencyRate::getLatestRate($currency->id);
                                    if ($latestRate) {
                                        $set('current_exchange_rate', $latestRate);
                                    }
                                } else {
                                    $set('current_exchange_rate', 1.0);
                                }
                            }
                        })
                        ->createOptionForm([
                            TextInput::make('code')
                                ->label(__('currency.code'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name')
                                ->label(__('currency.name'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('symbol')
                                ->label(__('currency.symbol'))
                                ->required()
                                ->maxLength(5),
                            TextInput::make('exchange_rate')
                                ->label(__('currency.exchange_rate'))
                                ->required()
                                ->numeric()
                                ->default(1),
                            Toggle::make('is_active')
                                ->label(__('currency.is_active'))
                                ->required()
                                ->default(true),
                        ])
                        ->createOptionModalHeading(__('common.modal_title_create_currency'))
                        ->createOptionAction(function (Action $action) {
                            return $action
                                ->modalWidth('lg');
                        }),

                    TextInput::make('current_exchange_rate')
                        ->label(__('invoice.current_exchange_rate'))
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(function (callable $get) {
                            $currencyId = $get('currency_id');
                            $company = \Filament\Facades\Filament::getTenant();
                            return $currencyId && $company && $currencyId != $company->currency_id;
                        })
                        ->helperText(__('invoice.exchange_rate_helper')),
                    TranslatableSelect::make('fiscal_position_id', \App\Models\FiscalPosition::class, __('invoice.fiscal_position')),
                    DatePicker::make('invoice_date')
                        ->label(__('invoice.invoice_date'))
                        ->default(now())
                        ->required()
                        ->rules([new NotInLockedPeriod()]),
                    DatePicker::make('due_date')
                        ->label(__('invoice.due_date'))
                        ->required(),
                    Select::make('status')
                        ->label(__('invoice.status'))
                        ->options(
                            collect(InvoiceStatus::cases())
                                ->mapWithKeys(fn (InvoiceStatus $status) => [$status->value => $status->label()])
                        )
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(2),

            Section::make(__('invoice.invoice_lines'))
                ->schema([
                    Repeater::make('invoiceLines')
                        ->label(__('invoice.invoice_lines'))
                        ->live()
                        ->reorderable(true)
                        ->deletable(fn (?Invoice $record) => !$record || $record->status === InvoiceStatus::Draft)
                        ->disabled(fn (?Invoice $record) => $record && $record->status !== InvoiceStatus::Draft)
                        ->minItems(1)
                        ->schema([
                            Select::make('product_id')
                                ->label(__('invoice.product'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array => Product::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->name)
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('description', $product->description);
                                            $set('unit_price', $product->unit_price);
                                            $set('income_account_id', $product->income_account_id);
                                        }
                                    }
                                })
                                ->columnSpan(2),
                            TextInput::make('description')
                                ->label(__('invoice.description'))
                                ->maxLength(255)
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('quantity')
                                ->label(__('invoice.quantity'))
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->columnSpan(1),
                            MoneyInput::make('unit_price')
                                ->label(__('invoice.unit_price'))
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(1),
                            Select::make('tax_id')
                                ->label(__('invoice.tax'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array =>
                                    Tax::whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.' . app()->getLocale() . '"))) LIKE ?', ['%' . strtolower($search) . '%'])
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($tax) => [$tax->id => $tax->getTranslation('name', app()->getLocale())])
                                        ->toArray()
                                )
                                ->getOptionLabelUsing(fn($value): ?string => Tax::find($value)?->getTranslation('name', app()->getLocale()))
                                ->columnSpan(1),
                            Select::make('income_account_id')
                                ->label(__('invoice.income_account'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array =>
                                    Account::where('type', 'Income')
                                        ->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.' . app()->getLocale() . '"))) LIKE ?', ['%' . strtolower($search) . '%'])
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($account) => [$account->id => $account->getTranslation('name', app()->getLocale())])
                                        ->toArray()
                                )
                                ->getOptionLabelUsing(fn($value): ?string => Account::find($value)?->getTranslation('name', app()->getLocale()))
                                ->required()
                                ->columnSpan(2),
                        ])
                        ->columns(5)
                        ->columnSpanFull(),
                ]),

            Section::make(__('invoice.company_currency_totals'))
                ->schema([
                    TextInput::make('exchange_rate_at_creation')
                        ->label(__('invoice.exchange_rate_at_creation'))
                        ->numeric()
                        ->disabled()
                        ->visible(fn (?Invoice $record) => $record && $record->exchange_rate_at_creation),

                    MoneyInput::make('total_amount_company_currency')
                        ->label(__('invoice.total_amount_company_currency'))
                        ->currencyField('../../company.currency_id')
                        ->disabled()
                        ->visible(fn (?Invoice $record) => $record && $record->total_amount_company_currency),

                    MoneyInput::make('total_tax_company_currency')
                        ->label(__('invoice.total_tax_company_currency'))
                        ->currencyField('../../company.currency_id')
                        ->disabled()
                        ->visible(fn (?Invoice $record) => $record && $record->total_tax_company_currency),
                ])
                ->columns(3)
                ->visible(fn (?Invoice $record) => $record && ($record->exchange_rate_at_creation || $record->total_amount_company_currency)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('invoice.company_name'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label(__('invoice.customer_name'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency.name')
                    ->label(__('invoice.currency_name'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('journalEntry.id')
                    ->label(__('invoice.journal_entry'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fiscalPosition.name')
                    ->label(__('invoice.fiscal_position_name'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->label(__('invoice.invoice_number'))
                    ->searchable(),
                TextColumn::make('invoice_date')
                    ->label(__('invoice.invoice_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label(__('invoice.due_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('invoice.status'))
                    ->badge()
                    ->colors([
                        'success' => InvoiceStatus::Posted,
                        'danger' => InvoiceStatus::Cancelled,
                        'warning' => InvoiceStatus::Draft,
                    ])
                    ->searchable(),
                TextColumn::make('paymentState')
                    ->label(__('invoice.payment_state'))
                    ->formatStateUsing(fn(PaymentState $state): string => $state->label())
                    ->badge()
                    ->color(fn(PaymentState $state): string => $state->color()),
                MoneyColumn::make('total_amount')
                    ->label(__('invoice.total_amount'))
                    ->sortable(),
                MoneyColumn::make('total_tax')
                    ->label(__('invoice.total_tax'))
                    ->sortable(),

                TextColumn::make('exchange_rate_at_creation')
                    ->label(__('invoice.exchange_rate'))
                    ->numeric(decimalPlaces: 6)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->exchange_rate_at_creation),

                MoneyColumn::make('total_amount_company_currency')
                    ->label(__('invoice.total_amount_company_currency'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => $record && $record->total_amount_company_currency),
                TextColumn::make('posted_at')
                    ->label(__('invoice.posted_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('invoice.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('invoice.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('viewPdf')
                        ->label(__('View PDF'))
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn (Invoice $record) => route('invoices.pdf', $record))
                        ->openUrlInNewTab(),

                    Action::make('downloadPdf')
                        ->label(__('Download PDF'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn (Invoice $record) => route('invoices.pdf.download', $record)),
                ])
                    ->label(__('PDF'))
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->button(),
                Action::make('confirm')
                    ->label(__('invoice.confirm'))
                    ->action(function (Invoice $record) {
                        $invoiceService = app(InvoiceService::class);
                        try {
                            $invoiceService->confirm($record, Auth::user());
                            Notification::make()
                                ->title(__('invoice.invoice_confirmed_successfully'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('invoice.error_confirming_invoice'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn(Invoice $record) => $record->status === InvoiceStatus::Draft),
                // Action::make('resetToDraft')
                //     ->label(__('invoice.reset_to_draft'))
                //     ->action(function (Invoice $record, array $data) {
                //         $invoiceService = app(InvoiceService::class);
                //         try {
                //             $invoiceService->resetToDraft($record, Auth::user(), $data['reason']);
                //             Notification::make()
                //                 ->title(__('invoice.invoice_reset_to_draft_successfully'))
                //                 ->success()
                //                 ->send();
                //         } catch (\Exception $e) {
                //             Notification::make()
                //                 ->title(__('invoice.error_resetting_invoice_to_draft'))
                //                 ->body($e->getMessage())
                //                 ->danger()
                //                 ->send();
                //         }
                //     })
                //     ->form([
                //         Forms\Components\Textarea::make('reason')->label(__('invoice.reason'))->required(),
                //     ])
                //     ->requiresConfirmation()
                //     ->visible(fn(Invoice $record) => $record->status === InvoiceStatus::Posted),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InvoiceLinesRelationManager::class,
            PaymentsRelationManager::class,
            AdjustmentDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}

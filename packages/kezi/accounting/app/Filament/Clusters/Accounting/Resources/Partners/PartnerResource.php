<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners;

use BackedEnum;
use \Filament\Actions\BulkActionGroup;
use \Filament\Actions\DeleteBulkAction;
use \Filament\Actions\EditAction;
use \Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\CreatePartner;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\EditPartner;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\ListPartners;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\ViewPartner;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\RelationManagers\InvoicesRelationManager;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\RelationManagers\PaymentsRelationManager;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\RelationManagers\UnreconciledEntriesRelationManager;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\RelationManagers\VendorBillsRelationManager;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Filament\Tables\Columns\MoneyColumn;
use Kezi\Foundation\Models\Partner;
use Xoshbin\CustomFields\Filament\Forms\Components\CustomFieldsComponent;
use Xoshbin\CustomFields\Filament\Tables\Components\CustomFieldTableColumns;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.contacts');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::partner.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::partner.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::partner.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::partner.basic_information'))
                    ->description(__('accounting::partner.basic_information_description'))
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('accounting::partner.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-building-office'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Select::make('type')
                                    ->label(__('accounting::partner.type'))
                                    ->required()
                                    ->searchable()
                                    ->options(
                                        collect(\Kezi\Foundation\Enums\Partners\PartnerType::cases())
                                            ->mapWithKeys(fn (\Kezi\Foundation\Enums\Partners\PartnerType $type) => [$type->value => $type->label()])
                                    )
                                    ->prefixIcon('heroicon-m-tag'),
                                TextInput::make('tax_id')
                                    ->label(__('accounting::partner.tax_id')) // Ensure this key exists or use 'Tax ID'
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-identification'),
                                Toggle::make('is_active')
                                    ->label(__('accounting::partner.is_active'))
                                    ->default(true)
                                    ->required()
                                    ->inline(false),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make(__('accounting::partner.contact_information'))
                    ->description(__('accounting::partner.contact_information_description'))
                    ->icon('heroicon-m-phone')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('contact_person')
                                    ->label(__('accounting::partner.contact_person'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-user'),
                                TextInput::make('email')
                                    ->label(__('accounting::partner.email'))
                                    ->email()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-envelope'),
                            ]),
                        TextInput::make('phone')
                            ->label(__('accounting::partner.phone'))
                            ->tel()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-phone')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make(__('accounting::partner.address_information'))
                    ->description(__('accounting::partner.address_information_description'))
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('address_line_1')
                                    ->label(__('accounting::partner.address_line_1'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-home'),
                                TextInput::make('address_line_2')
                                    ->label(__('accounting::partner.address_line_2'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-home'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('city')
                                    ->label(__('accounting::partner.city'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-building-office-2'),
                                TextInput::make('state')
                                    ->label(__('accounting::partner.state'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-map'),
                                TextInput::make('zip_code')
                                    ->label(__('accounting::partner.zip_code'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-hashtag'),
                            ]),
                        TextInput::make('country')
                            ->label(__('accounting::partner.country'))
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-globe-alt')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make(__('accounting::partner.accounting_configuration'))
                    ->description(__('accounting::partner.accounting_configuration_description'))
                    ->icon('heroicon-m-calculator')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TranslatableSelect::forModel('receivable_account_id', Account::class)
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Hidden::make('company_id')
                                            ->default(function (): ?int {
                                                $tenant = Filament::getTenant();

                                                return $tenant?->getKey();
                                            }),
                                        TextInput::make('name')
                                            ->label(__('accounting::account.name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('code')
                                            ->label(__('accounting::account.code'))
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('type')
                                            ->label(__('accounting::account.type'))
                                            ->options([\Kezi\Accounting\Enums\Accounting\AccountType::Receivable->value => \Kezi\Accounting\Enums\Accounting\AccountType::Receivable->label()])
                                            ->default(\Kezi\Accounting\Enums\Accounting\AccountType::Receivable->value)
                                            ->required(),
                                    ])
                                    ->createOptionModalHeading(__('accounting::partner.create_receivable_account'))
                                    ->helperText(__('accounting::partner.receivable_account_help'))
                                    ->prefixIcon('heroicon-m-arrow-trending-up'),

                                TranslatableSelect::forModel('payable_account_id', Account::class)
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Hidden::make('company_id')
                                            ->default(function (): ?int {
                                                $tenant = Filament::getTenant();

                                                return $tenant?->getKey();
                                            }),
                                        TextInput::make('name')
                                            ->label(__('accounting::account.name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('code')
                                            ->label(__('accounting::account.code'))
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('type')
                                            ->label(__('accounting::account.type'))
                                            ->searchable()
                                            ->options([\Kezi\Accounting\Enums\Accounting\AccountType::Payable->value => \Kezi\Accounting\Enums\Accounting\AccountType::Payable->label()])
                                            ->default(\Kezi\Accounting\Enums\Accounting\AccountType::Payable->value)
                                            ->required(),
                                    ])
                                    ->createOptionModalHeading(__('accounting::partner.create_payable_account'))
                                    ->helperText(__('accounting::partner.payable_account_help'))
                                    ->prefixIcon('heroicon-m-arrow-trending-down'),
                            ]),

                        Grid::make(1)
                            ->schema([
                                Select::make('linked_company_id')
                                    ->label(__('accounting::partner.linked_company'))
                                    ->relationship('linkedCompany', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText(__('accounting::partner.linked_company_help'))
                                    ->prefixIcon('heroicon-m-building-office-2'),
                                Select::make('withholding_tax_type_id')
                                    ->label(__('accounting::withholding_tax.label')) // Assuming translation key exists, or use 'Withholding Tax Type'
                                    ->relationship('withholdingTaxType', 'name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' ('.$record->rate * 100 .'%)')
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-m-scissors'),
                                Select::make('fiscal_position_id')
                                    ->label(__('accounting::partner.fiscal_position'))
                                    ->relationship('fiscalPosition', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-m-globe-alt'),
                                TranslatableSelect::forModel('default_tax_id', Tax::class, 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Hidden::make('company_id')
                                            ->default(function (): ?int {
                                                $tenant = Filament::getTenant();

                                                return $tenant?->getKey();
                                            }),
                                        TextInput::make('name')
                                            ->label(__('accounting::tax.name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('rate')
                                            ->label(__('accounting::tax.rate'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(1)
                                            ->step(0.01),
                                        Select::make('type')
                                            ->label(__('accounting::tax.type'))
                                            ->options([
                                                \Kezi\Accounting\Enums\Accounting\TaxType::Sales->value => \Kezi\Accounting\Enums\Accounting\TaxType::Sales->label(),
                                                \Kezi\Accounting\Enums\Accounting\TaxType::Purchase->value => \Kezi\Accounting\Enums\Accounting\TaxType::Purchase->label(),
                                                \Kezi\Accounting\Enums\Accounting\TaxType::Both->value => \Kezi\Accounting\Enums\Accounting\TaxType::Both->label(),
                                            ])
                                            ->default(\Kezi\Accounting\Enums\Accounting\TaxType::Both->value)
                                            ->required(),
                                    ])
                                    ->createOptionModalHeading(__('accounting::partner.create_default_tax'))
                                    ->helperText(__('accounting::partner.default_tax_help'))
                                    ->prefixIcon('heroicon-m-percent-badge'),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

                // Custom Fields Section
                CustomFieldsComponent::make(Partner::class),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Name (most important for identification)
                TextColumn::make('name')
                    ->label(__('accounting::partner.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                // Type (critical for categorization)
                TextColumn::make('type')
                    ->label(__('accounting::partner.type'))
                    ->formatStateUsing(fn (\Kezi\Foundation\Enums\Partners\PartnerType $state): string => $state->label())
                    ->badge()
                    ->color(fn (\Kezi\Foundation\Enums\Partners\PartnerType $state): string => match ($state) {
                        \Kezi\Foundation\Enums\Partners\PartnerType::Customer => 'success',
                        \Kezi\Foundation\Enums\Partners\PartnerType::Vendor => 'info',
                        \Kezi\Foundation\Enums\Partners\PartnerType::Both => 'warning',
                    })
                    ->icons([
                        'heroicon-m-user' => \Kezi\Foundation\Enums\Partners\PartnerType::Customer,
                        'heroicon-m-building-office' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor,
                        'heroicon-m-user-group' => \Kezi\Foundation\Enums\Partners\PartnerType::Both,
                    ])
                    ->searchable()
                    ->sortable(),

                // Status (important for active/inactive)
                TextColumn::make('is_active')
                    ->label(__('accounting::partner.status'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('accounting::partner.active') : __('accounting::partner.inactive'))
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                    ->sortable(),

                // Financial Information - Customer Balances
                MoneyColumn::make('customer_balance')
                    ->label(__('accounting::partner.customer_outstanding'))
                    ->getStateUsing(function (Partner $record) {
                        if (! in_array($record->type, [\Kezi\Foundation\Enums\Partners\PartnerType::Customer, \Kezi\Foundation\Enums\Partners\PartnerType::Both])) {
                            return null;
                        }

                        return $record->getCustomerOutstandingBalance();
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (! in_array($record->type, [\Kezi\Foundation\Enums\Partners\PartnerType::Customer, \Kezi\Foundation\Enums\Partners\PartnerType::Both])) {
                            return 'gray';
                        }

                        return $record->getCustomerOutstandingBalance()->isZero() ? 'gray' : 'success';
                    })
                    ->sortable(false),

                MoneyColumn::make('customer_overdue')
                    ->label(__('accounting::partner.customer_overdue'))
                    ->getStateUsing(function (Partner $record) {
                        if (! in_array($record->type, [\Kezi\Foundation\Enums\Partners\PartnerType::Customer, \Kezi\Foundation\Enums\Partners\PartnerType::Both])) {
                            return null;
                        }

                        return $record->getCustomerOverdueBalance();
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (! in_array($record->type, [\Kezi\Foundation\Enums\Partners\PartnerType::Customer, \Kezi\Foundation\Enums\Partners\PartnerType::Both])) {
                            return 'gray';
                        }

                        return $record->getCustomerOverdueBalance()->isZero() ? 'gray' : 'warning';
                    })
                    ->sortable(false),

                // Financial Information - Vendor Balances
                MoneyColumn::make('vendor_balance')
                    ->label(__('accounting::partner.vendor_outstanding'))
                    ->getStateUsing(function (Partner $record) {
                        if (! in_array($record->type, [\Kezi\Foundation\Enums\Partners\PartnerType::Vendor, \Kezi\Foundation\Enums\Partners\PartnerType::Both])) {
                            return null;
                        }

                        return $record->getVendorOutstandingBalance();
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (! in_array($record->type, [\Kezi\Foundation\Enums\Partners\PartnerType::Vendor, \Kezi\Foundation\Enums\Partners\PartnerType::Both])) {
                            return 'gray';
                        }

                        return $record->getVendorOutstandingBalance()->isZero() ? 'gray' : 'danger';
                    })
                    ->sortable(false),

                MoneyColumn::make('vendor_overdue')
                    ->label(__('accounting::partner.vendor_overdue'))
                    ->getStateUsing(function (Partner $record) {
                        if (! in_array($record->type, [\Kezi\Foundation\Enums\Partners\PartnerType::Vendor, \Kezi\Foundation\Enums\Partners\PartnerType::Both])) {
                            return null;
                        }

                        return $record->getVendorOverdueBalance();
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (! in_array($record->type, [\Kezi\Foundation\Enums\Partners\PartnerType::Vendor, \Kezi\Foundation\Enums\Partners\PartnerType::Both])) {
                            return 'gray';
                        }

                        return $record->getVendorOverdueBalance()->isZero() ? 'gray' : 'warning';
                    })
                    ->sortable(false),

                // Last Activity
                TextColumn::make('last_activity')
                    ->label(__('accounting::partner.last_activity'))
                    ->getStateUsing(
                        fn (Partner $record): string => $record->getLastTransactionDate()?->format('M j, Y') ?? __('accounting::partner.no_activity')
                    )
                    ->sortable(false)
                    ->toggleable(),

                // Custom Fields (dynamic columns)
                ...CustomFieldTableColumns::make(Partner::class),

                // Contact Information (toggleable)
                TextColumn::make('contact_person')
                    ->label(__('accounting::partner.contact_person'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label(__('accounting::partner.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label(__('accounting::partner.phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Address Information (toggleable)
                TextColumn::make('address_line_1')
                    ->label(__('accounting::partner.address_line_1'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('city')
                    ->label(__('accounting::partner.city'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country')
                    ->label(__('accounting::partner.country'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tax_id')
                    ->label(__('accounting::partner.tax_id'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('defaultTax.name')
                    ->label(__('accounting::partner.default_tax'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Status
                IconColumn::make('is_active')
                    ->label(__('accounting::partner.is_active'))
                    ->boolean(),

                // Timestamps (hidden by default)
                TextColumn::make('created_at')
                    ->label(__('accounting::partner.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('accounting::partner.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('accounting::partner.type'))
                    ->options([
                        'customer' => __('accounting::enums.partner_type.customer'),
                        'vendor' => __('accounting::enums.partner_type.vendor'),
                        'both' => __('accounting::enums.partner_type.both'),
                    ]),

                Filter::make('has_overdue')
                    ->label(__('accounting::partner.has_overdue_amounts'))
                    ->query(
                        fn (Builder $query): Builder => $query->whereHas('invoices', function ($q) {
                            $q->whereIn('status', ['posted', 'paid'])
                                ->where('due_date', '<', now())
                                ->whereRaw('total_amount > (
                                  SELECT COALESCE(SUM(amount_applied), 0)
                                  FROM payment_document_links
                                  WHERE invoice_id = invoices.id
                              )');
                        })->orWhereHas('vendorBills', function ($q) {
                            $q->whereIn('status', ['posted', 'paid'])
                                ->where('due_date', '<', now())
                                ->whereRaw('total_amount > (
                                  SELECT COALESCE(SUM(amount_applied), 0)
                                  FROM payment_document_links
                                  WHERE vendor_bill_id = vendor_bills.id
                              )');
                        })
                    ),

                Filter::make('has_outstanding_balance')
                    ->label(__('accounting::partner.has_outstanding_balance'))
                    ->query(
                        fn (Builder $query): Builder => $query->whereHas('invoices', function ($q) {
                            $q->whereIn('status', ['posted', 'paid'])
                                ->whereRaw('total_amount > (
                                  SELECT COALESCE(SUM(amount_applied), 0)
                                  FROM payment_document_links
                                  WHERE invoice_id = invoices.id
                              )');
                        })->orWhereHas('vendorBills', function ($q) {
                            $q->whereIn('status', ['posted', 'paid'])
                                ->whereRaw('total_amount > (
                                  SELECT COALESCE(SUM(amount_applied), 0)
                                  FROM payment_document_links
                                  WHERE vendor_bill_id = vendor_bills.id
                              )');
                        })
                    ),

                TernaryFilter::make('is_active')
                    ->label(__('accounting::partner.is_active')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InvoicesRelationManager::class,
            VendorBillsRelationManager::class,
            PaymentsRelationManager::class,
            UnreconciledEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartners::route('/'),
            'create' => CreatePartner::route('/create'),
            'view' => ViewPartner::route('/{record}'),
            'edit' => EditPartner::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Clusters\Accounting\Resources\Partners;

use App\Enums\Accounting\AccountType;
use App\Enums\Partners\PartnerType;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\Partners\Pages\CreatePartner;
use App\Filament\Clusters\Accounting\Resources\Partners\Pages\EditPartner;
use App\Filament\Clusters\Accounting\Resources\Partners\Pages\ListPartners;
use App\Filament\Clusters\Accounting\Resources\Partners\Pages\ViewPartner;
use App\Filament\Clusters\Accounting\Resources\Partners\RelationManagers\InvoicesRelationManager;
use App\Filament\Clusters\Accounting\Resources\Partners\RelationManagers\PaymentsRelationManager;
use App\Filament\Clusters\Accounting\Resources\Partners\RelationManagers\UnreconciledEntriesRelationManager;
use App\Filament\Clusters\Accounting\Resources\Partners\RelationManagers\VendorBillsRelationManager;
use App\Filament\Support\TranslatableSelect;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Account;
use App\Models\Partner;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 6;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.sales_purchases');
    }

    public static function getModelLabel(): string
    {
        return __('partner.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('partner.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('partner.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('partner.basic_information'))
                    ->description(__('partner.basic_information_description'))
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('partner.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-building-office'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Select::make('type')
                                    ->label(__('partner.type'))
                                    ->required()
                                    ->options(
                                        collect(PartnerType::cases())
                                            ->mapWithKeys(fn (PartnerType $type) => [$type->value => $type->label()])
                                    )
                                    ->prefixIcon('heroicon-m-tag'),
                                TextInput::make('tax_id')
                                    ->label(__('partner.tax_id'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-document-text'),
                                Toggle::make('is_active')
                                    ->label(__('partner.is_active'))
                                    ->default(true)
                                    ->required()
                                    ->inline(false),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make(__('partner.contact_information'))
                    ->description(__('partner.contact_information_description'))
                    ->icon('heroicon-m-phone')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('contact_person')
                                    ->label(__('partner.contact_person'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-user'),
                                TextInput::make('email')
                                    ->label(__('partner.email'))
                                    ->email()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-envelope'),
                            ]),
                        TextInput::make('phone')
                            ->label(__('partner.phone'))
                            ->tel()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-phone')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make(__('partner.address_information'))
                    ->description(__('partner.address_information_description'))
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('address_line_1')
                                    ->label(__('partner.address_line_1'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-home'),
                                TextInput::make('address_line_2')
                                    ->label(__('partner.address_line_2'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-home'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('city')
                                    ->label(__('partner.city'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-building-office-2'),
                                TextInput::make('state')
                                    ->label(__('partner.state'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-map'),
                                TextInput::make('zip_code')
                                    ->label(__('partner.zip_code'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-hashtag'),
                            ]),
                        TextInput::make('country')
                            ->label(__('partner.country'))
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-globe-alt')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make(__('partner.accounting_configuration'))
                    ->description(__('partner.accounting_configuration_description'))
                    ->icon('heroicon-m-calculator')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TranslatableSelect::relationship(
                                    'receivable_account_id',
                                    'receivableAccount',
                                    Account::class,
                                    __('partner.receivable_account'),
                                    'name',
                                    null,
                                    fn ($query) => $query->where('type', AccountType::Receivable)
                                )
                                    ->preload()
                                    ->createOptionForm([
                                        Hidden::make('company_id')
                                            ->default(function (): ?int {
                                                $tenant = Filament::getTenant();
                                                return method_exists($tenant, 'getKey') ? (int) $tenant->getKey() : null;
                                            }),
                                        TextInput::make('name')
                                            ->label(__('account.name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('code')
                                            ->label(__('account.code'))
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('type')
                                            ->label(__('account.type'))
                                            ->options([AccountType::Receivable->value => AccountType::Receivable->label()])
                                            ->default(AccountType::Receivable->value)
                                            ->required(),
                                    ])
                                    ->createOptionModalHeading(__('partner.create_receivable_account'))
                                    ->helperText(__('partner.receivable_account_help'))
                                    ->prefixIcon('heroicon-m-arrow-trending-up'),

                                TranslatableSelect::relationship(
                                    'payable_account_id',
                                    'payableAccount',
                                    Account::class,
                                    __('partner.payable_account'),
                                    'name',
                                    null,
                                    fn ($query) => $query->where('type', AccountType::Payable)
                                )
                                    ->preload()
                                    ->createOptionForm([
                                        Hidden::make('company_id')
                                            ->default(function (): ?int {
                                                $tenant = Filament::getTenant();
                                                return method_exists($tenant, 'getKey') ? (int) $tenant->getKey() : null;
                                            }),
                                        TextInput::make('name')
                                            ->label(__('account.name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('code')
                                            ->label(__('account.code'))
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('type')
                                            ->label(__('account.type'))
                                            ->options([AccountType::Payable->value => AccountType::Payable->label()])
                                            ->default(AccountType::Payable->value)
                                            ->required(),
                                    ])
                                    ->createOptionModalHeading(__('partner.create_payable_account'))
                                    ->helperText(__('partner.payable_account_help'))
                                    ->prefixIcon('heroicon-m-arrow-trending-down'),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Name (most important for identification)
                TextColumn::make('name')
                    ->label(__('partner.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                // Type (critical for categorization)
                TextColumn::make('type')
                    ->label(__('partner.type'))
                    ->formatStateUsing(fn (PartnerType $state): string => $state->label())
                    ->badge()
                    ->color(fn (PartnerType $state): string => match ($state) {
                        PartnerType::Customer => 'success',
                        PartnerType::Vendor => 'info',
                        PartnerType::Both => 'warning',
                    })
                    ->icons([
                        'heroicon-m-user' => PartnerType::Customer,
                        'heroicon-m-building-office' => PartnerType::Vendor,
                        'heroicon-m-user-group' => PartnerType::Both,
                    ])
                    ->searchable()
                    ->sortable(),

                // Status (important for active/inactive)
                TextColumn::make('is_active')
                    ->label(__('partner.status'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('partner.active') : __('partner.inactive'))
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                    ->sortable(),

                // Financial Information - Customer Balances
                MoneyColumn::make('customer_balance')
                    ->label(__('partner.customer_outstanding'))
                    ->getStateUsing(function (Partner $record) {
                        if (! in_array($record->type, [PartnerType::Customer, PartnerType::Both])) {
                            return null;
                        }

                        return $record->getCustomerOutstandingBalance();
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (! in_array($record->type, [PartnerType::Customer, PartnerType::Both])) {
                            return 'gray';
                        }

                        return $record->getCustomerOutstandingBalance()->isZero() ? 'gray' : 'success';
                    })
                    ->sortable(false),

                MoneyColumn::make('customer_overdue')
                    ->label(__('partner.customer_overdue'))
                    ->getStateUsing(function (Partner $record) {
                        if (! in_array($record->type, [PartnerType::Customer, PartnerType::Both])) {
                            return null;
                        }

                        return $record->getCustomerOverdueBalance();
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (! in_array($record->type, [PartnerType::Customer, PartnerType::Both])) {
                            return 'gray';
                        }

                        return $record->getCustomerOverdueBalance()->isZero() ? 'gray' : 'warning';
                    })
                    ->sortable(false),

                // Financial Information - Vendor Balances
                MoneyColumn::make('vendor_balance')
                    ->label(__('partner.vendor_outstanding'))
                    ->getStateUsing(function (Partner $record) {
                        if (! in_array($record->type, [PartnerType::Vendor, PartnerType::Both])) {
                            return null;
                        }

                        return $record->getVendorOutstandingBalance();
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (! in_array($record->type, [PartnerType::Vendor, PartnerType::Both])) {
                            return 'gray';
                        }

                        return $record->getVendorOutstandingBalance()->isZero() ? 'gray' : 'danger';
                    })
                    ->sortable(false),

                MoneyColumn::make('vendor_overdue')
                    ->label(__('partner.vendor_overdue'))
                    ->getStateUsing(function (Partner $record) {
                        if (! in_array($record->type, [PartnerType::Vendor, PartnerType::Both])) {
                            return null;
                        }

                        return $record->getVendorOverdueBalance();
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (! in_array($record->type, [PartnerType::Vendor, PartnerType::Both])) {
                            return 'gray';
                        }

                        return $record->getVendorOverdueBalance()->isZero() ? 'gray' : 'warning';
                    })
                    ->sortable(false),

                // Last Activity
                TextColumn::make('last_activity')
                    ->label(__('partner.last_activity'))
                    ->getStateUsing(fn (Partner $record): string => $record->getLastTransactionDate()?->format('M j, Y') ?? __('partner.no_activity')
                    )
                    ->sortable(false)
                    ->toggleable(),

                // Contact Information (toggleable)
                TextColumn::make('contact_person')
                    ->label(__('partner.contact_person'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label(__('partner.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label(__('partner.phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Address Information (toggleable)
                TextColumn::make('address_line_1')
                    ->label(__('partner.address_line_1'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('city')
                    ->label(__('partner.city'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country')
                    ->label(__('partner.country'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tax_id')
                    ->label(__('partner.tax_id'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Status
                IconColumn::make('is_active')
                    ->label(__('partner.is_active'))
                    ->boolean(),

                // Timestamps (hidden by default)
                TextColumn::make('created_at')
                    ->label(__('partner.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('partner.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('partner.type'))
                    ->options([
                        'customer' => __('enums.partner_type.customer'),
                        'vendor' => __('enums.partner_type.vendor'),
                        'both' => __('enums.partner_type.both'),
                    ]),

                Filter::make('has_overdue')
                    ->label(__('partner.has_overdue_amounts'))
                    ->query(fn (Builder $query): Builder => $query->whereHas('invoices', function ($q) {
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
                    ->label(__('partner.has_outstanding_balance'))
                    ->query(fn (Builder $query): Builder => $query->whereHas('invoices', function ($q) {
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
                    ->label(__('partner.is_active')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
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

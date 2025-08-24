<?php

namespace App\Filament\Resources\Partners;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use App\Filament\Support\TranslatableSelect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use App\Enums\Accounting\AccountType;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Partners\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\Partners\RelationManagers\VendorBillsRelationManager;
use App\Filament\Resources\Partners\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Partners\RelationManagers\UnreconciledEntriesRelationManager;
use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Filament\Resources\Partners\Pages\CreatePartner;
use App\Filament\Resources\Partners\Pages\ViewPartner;
use App\Filament\Resources\Partners\Pages\EditPartner;
use App\Filament\Resources\PartnerResource\Pages;
use App\Filament\Resources\PartnerResource\RelationManagers;
use App\Models\Partner;
use App\Enums\Partners\PartnerType;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.sales_purchases');
    }

    public static function getLabel(): ?string
    {
        return __('partner.label');
    }

    public static function getPluralLabel(): ?string
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
                                    \App\Models\Account::class,
                                    __('partner.receivable_account'),
                                    'name',
                                    null,
                                    fn($query) => $query->where('type', AccountType::Receivable)
                                )
                                    ->preload()
                                    ->createOptionForm([
                                        \Filament\Forms\Components\Hidden::make('company_id')
                                            ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
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
                                            ->options([AccountType::Receivable->value => 'Receivable'])
                                            ->default(AccountType::Receivable->value)
                                            ->required(),
                                    ])
                                    ->createOptionModalHeading(__('partner.create_receivable_account'))
                                    ->helperText(__('partner.receivable_account_help'))
                                    ->prefixIcon('heroicon-m-arrow-trending-up'),

                                TranslatableSelect::relationship(
                                    'payable_account_id',
                                    'payableAccount',
                                    \App\Models\Account::class,
                                    __('partner.payable_account'),
                                    'name',
                                    null,
                                    fn($query) => $query->where('type', AccountType::Payable)
                                )
                                    ->preload()
                                    ->createOptionForm([
                                        \Filament\Forms\Components\Hidden::make('company_id')
                                            ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
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
                                            ->options([AccountType::Payable->value => 'Payable'])
                                            ->default(AccountType::Payable->value)
                                            ->required(),
                                    ])
                                    ->createOptionModalHeading(__('partner.create_payable_account'))
                                    ->helperText(__('partner.payable_account_help'))
                                    ->prefixIcon('heroicon-m-arrow-trending-down'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('partner.company'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label(__('partner.name'))
                    ->searchable()
                    ->sortable(),
                SelectColumn::make('type')
                    ->label(__('partner.type'))
                    ->searchable()
                    ->options(
                        collect(PartnerType::cases())
                            ->mapWithKeys(fn (PartnerType $type) => [$type->value => $type->label()])
                    ),

                // Financial Information - Customer Balances
                TextColumn::make('customer_balance')
                    ->label(__('partner.customer_outstanding'))
                    ->getStateUsing(function (Partner $record): string {
                        if (!in_array($record->type, [PartnerType::Customer, PartnerType::Both])) {
                            return '-';
                        }
                        return $record->getCustomerOutstandingBalance()->formatTo('en_US');
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (!in_array($record->type, [PartnerType::Customer, PartnerType::Both])) {
                            return 'gray';
                        }
                        return $record->getCustomerOutstandingBalance()->isZero() ? 'gray' : 'success';
                    })
                    ->sortable(false),

                TextColumn::make('customer_overdue')
                    ->label(__('partner.customer_overdue'))
                    ->getStateUsing(function (Partner $record): string {
                        if (!in_array($record->type, [PartnerType::Customer, PartnerType::Both])) {
                            return '-';
                        }
                        return $record->getCustomerOverdueBalance()->formatTo('en_US');
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (!in_array($record->type, [PartnerType::Customer, PartnerType::Both])) {
                            return 'gray';
                        }
                        return $record->getCustomerOverdueBalance()->isZero() ? 'gray' : 'warning';
                    })
                    ->sortable(false),

                // Financial Information - Vendor Balances
                TextColumn::make('vendor_balance')
                    ->label(__('partner.vendor_outstanding'))
                    ->getStateUsing(function (Partner $record): string {
                        if (!in_array($record->type, [PartnerType::Vendor, PartnerType::Both])) {
                            return '-';
                        }
                        return $record->getVendorOutstandingBalance()->formatTo('en_US');
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (!in_array($record->type, [PartnerType::Vendor, PartnerType::Both])) {
                            return 'gray';
                        }
                        return $record->getVendorOutstandingBalance()->isZero() ? 'gray' : 'danger';
                    })
                    ->sortable(false),

                TextColumn::make('vendor_overdue')
                    ->label(__('partner.vendor_overdue'))
                    ->getStateUsing(function (Partner $record): string {
                        if (!in_array($record->type, [PartnerType::Vendor, PartnerType::Both])) {
                            return '-';
                        }
                        return $record->getVendorOverdueBalance()->formatTo('en_US');
                    })
                    ->badge()
                    ->color(function (Partner $record) {
                        if (!in_array($record->type, [PartnerType::Vendor, PartnerType::Both])) {
                            return 'gray';
                        }
                        return $record->getVendorOverdueBalance()->isZero() ? 'gray' : 'warning';
                    })
                    ->sortable(false),

                // Last Activity
                TextColumn::make('last_activity')
                    ->label(__('partner.last_activity'))
                    ->getStateUsing(fn (Partner $record): string =>
                        $record->getLastTransactionDate()?->format('M j, Y') ?? __('partner.no_activity')
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
                    ->query(fn (Builder $query): Builder =>
                        $query->whereHas('invoices', function ($q) {
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
                    ->query(fn (Builder $query): Builder =>
                        $query->whereHas('invoices', function ($q) {
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

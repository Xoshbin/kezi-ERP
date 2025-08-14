<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Filament\Resources\PartnerResource\RelationManagers;
use App\Models\Partner;
use App\Enums\Partners\PartnerType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('partner.basic_information'))
                    ->description(__('partner.basic_information_description'))
                    ->icon('heroicon-m-user')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('partner.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-building-office'),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label(__('partner.type'))
                                    ->required()
                                    ->options(
                                        collect(PartnerType::cases())
                                            ->mapWithKeys(fn (PartnerType $type) => [$type->value => $type->label()])
                                    )
                                    ->prefixIcon('heroicon-m-tag'),
                                Forms\Components\TextInput::make('tax_id')
                                    ->label(__('partner.tax_id'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-document-text'),
                                Forms\Components\Toggle::make('is_active')
                                    ->label(__('partner.is_active'))
                                    ->default(true)
                                    ->required()
                                    ->inline(false),
                            ]),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('partner.contact_information'))
                    ->description(__('partner.contact_information_description'))
                    ->icon('heroicon-m-phone')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('contact_person')
                                    ->label(__('partner.contact_person'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-user'),
                                Forms\Components\TextInput::make('email')
                                    ->label(__('partner.email'))
                                    ->email()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-envelope'),
                            ]),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('partner.phone'))
                            ->tel()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-phone')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make(__('partner.address_information'))
                    ->description(__('partner.address_information_description'))
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('address_line_1')
                                    ->label(__('partner.address_line_1'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-home'),
                                Forms\Components\TextInput::make('address_line_2')
                                    ->label(__('partner.address_line_2'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-home'),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->label(__('partner.city'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-building-office-2'),
                                Forms\Components\TextInput::make('state')
                                    ->label(__('partner.state'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-map'),
                                Forms\Components\TextInput::make('zip_code')
                                    ->label(__('partner.zip_code'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-hashtag'),
                            ]),
                        Forms\Components\TextInput::make('country')
                            ->label(__('partner.country'))
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-globe-alt')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make(__('partner.accounting_configuration'))
                    ->description(__('partner.accounting_configuration_description'))
                    ->icon('heroicon-m-calculator')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('receivable_account_id')
                                    ->label(__('partner.receivable_account'))
                                    ->relationship('receivableAccount', 'name', function ($query) {
                                        return $query->where('type', \App\Enums\Accounting\AccountType::Receivable);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('account.name'))
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('code')
                                            ->label(__('account.code'))
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('type')
                                            ->label(__('account.type'))
                                            ->options([\App\Enums\Accounting\AccountType::Receivable->value => 'Receivable'])
                                            ->default(\App\Enums\Accounting\AccountType::Receivable->value)
                                            ->required(),
                                    ])
                                    ->createOptionModalHeading(__('partner.create_receivable_account'))
                                    ->helperText(__('partner.receivable_account_help'))
                                    ->prefixIcon('heroicon-m-arrow-trending-up'),

                                Forms\Components\Select::make('payable_account_id')
                                    ->label(__('partner.payable_account'))
                                    ->relationship('payableAccount', 'name', function ($query) {
                                        return $query->where('type', \App\Enums\Accounting\AccountType::Payable);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('account.name'))
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('code')
                                            ->label(__('account.code'))
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('type')
                                            ->label(__('account.type'))
                                            ->options([\App\Enums\Accounting\AccountType::Payable->value => 'Payable'])
                                            ->default(\App\Enums\Accounting\AccountType::Payable->value)
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
                Tables\Columns\TextColumn::make('name')
                    ->label(__('partner.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\SelectColumn::make('type')
                    ->label(__('partner.type'))
                    ->searchable()
                    ->options(
                        collect(PartnerType::cases())
                            ->mapWithKeys(fn (PartnerType $type) => [$type->value => $type->label()])
                    ),

                // Financial Information - Customer Balances
                Tables\Columns\TextColumn::make('customer_balance')
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

                Tables\Columns\TextColumn::make('customer_overdue')
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
                Tables\Columns\TextColumn::make('vendor_balance')
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

                Tables\Columns\TextColumn::make('vendor_overdue')
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
                Tables\Columns\TextColumn::make('last_activity')
                    ->label(__('partner.last_activity'))
                    ->getStateUsing(fn (Partner $record): string =>
                        $record->getLastTransactionDate()?->format('M j, Y') ?? __('partner.no_activity')
                    )
                    ->sortable(false)
                    ->toggleable(),

                // Contact Information (toggleable)
                Tables\Columns\TextColumn::make('contact_person')
                    ->label(__('partner.contact_person'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('partner.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('partner.phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Address Information (toggleable)
                Tables\Columns\TextColumn::make('address_line_1')
                    ->label(__('partner.address_line_1'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('city')
                    ->label(__('partner.city'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('country')
                    ->label(__('partner.country'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tax_id')
                    ->label(__('partner.tax_id'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Status
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('partner.is_active'))
                    ->boolean(),

                // Timestamps (hidden by default)
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('partner.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('partner.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('partner.type'))
                    ->options([
                        'customer' => __('enums.partner_type.customer'),
                        'vendor' => __('enums.partner_type.vendor'),
                        'both' => __('enums.partner_type.both'),
                    ]),

                Tables\Filters\Filter::make('has_overdue')
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

                Tables\Filters\Filter::make('has_outstanding_balance')
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

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('partner.is_active')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InvoicesRelationManager::class,
            RelationManagers\VendorBillsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'view' => Pages\ViewPartner::route('/{record}'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}

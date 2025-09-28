<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments;

use BackedEnum;
use App\Models\Company;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Modules\Payment\Models\Payment;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Components\Group;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Partner;
use Filament\Tables\Columns\TextColumn;
use Modules\Foundation\Models\Currency;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ToggleButtons;
use Modules\Payment\Services\PaymentService;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Foundation\Filament\Tables\Columns\MoneyColumn;
use Modules\Foundation\Filament\Forms\Components\MoneyInput;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages\ListPayments;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages\CreatePayment;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\InvoicesRelationManager;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\VendorBillsRelationManager;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\JournalEntriesRelationManager;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\BankStatementLinesRelationManager;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.banking_cash');
    }

    public static function getModelLabel(): string
    {
        return __('payment.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payment.model_plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('payment.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payment.form.payment_information'))
                ->description(__('payment.form.direct_payment_description'))
                ->compact()
                ->schema([
                    Group::make()
                        ->schema([
                            ToggleButtons::make('payment_type')
                                ->label(__('payment.form.payment_type'))
                                ->options([
                                    PaymentType::Inbound->value => __('payment.form.receive') ?: PaymentType::Inbound->label(),
                                    PaymentType::Outbound->value => __('payment.form.send') ?: PaymentType::Outbound->label(),
                                ])
                                ->colors([
                                    PaymentType::Inbound->value => 'success',
                                    PaymentType::Outbound->value => 'danger',
                                ])
                                ->icons([
                                    PaymentType::Inbound->value => 'heroicon-m-arrow-down-circle',
                                    PaymentType::Outbound->value => 'heroicon-m-arrow-up-circle',
                                ])
                                ->inline()
                                ->required()
                                ->columnSpanFull(),

                            TranslatableSelect::forModel('paid_to_from_partner_id', Partner::class, 'name')
                                ->searchable()
                                ->label(__('payment.form.partner'))
                                ->searchableFields(['name', 'tax_id'])
                                ->preload()
                                ->required()
                                ->columnSpanFull(),

                            MoneyInput::make('amount')
                                ->label(__('payment.form.amount'))
                                ->currencyField('currency_id')
                                ->required()
                                ->columnSpanFull(),

                            Group::make()
                                ->schema([
                                    DatePicker::make('payment_date')
                                        ->default(now())
                                        ->label(__('payment.form.payment_date'))
                                        ->required()
                                        ->columnSpan(6),
                                    TextInput::make('reference')
                                        ->label(__('payment.form.reference'))
                                        ->maxLength(255)
                                        ->columnSpan(6),
                                ])
                                ->columns(12)
                                ->columnSpanFull(),
                        ])
                        ->columns(12)
                        ->columnSpan(8),

                    Group::make()
                        ->schema([
                            TranslatableSelect::forModel('journal_id', Journal::class, 'name')
                                ->label(__('payment.form.journal_id'))
                                ->searchable()
                                ->relationship('journal', 'name')
                                ->label(__('payment.form.journal_id'))
                                ->searchableFields(['name', 'code'])
                                ->preload()
                                ->required()
                                ->columnSpanFull(),
                            Select::make('payment_method')
                                ->label(__('payment.form.payment_method'))
                                ->options(collect(PaymentMethod::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                                ->searchable()
                                ->required()
                                ->columnSpanFull(),
                            TranslatableSelect::forModel('currency_id', Currency::class, 'name')
                                ->label(__('payment.form.currency_id'))
                                ->searchableFields(['name', 'code'])
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default(function (): ?int {
                                    $tenant = Filament::getTenant();

                                    return $tenant instanceof Company ? $tenant->currency_id : null;
                                })
                                ->columnSpanFull(),
                        ])
                        ->columns(12)
                        ->columnSpan(4),
                ])
                ->columns(12)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Reference (most important for identification)
                TextColumn::make('reference')
                    ->label(__('payment.reference'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->getStateUsing(function (Payment $record): string {
                        if ($record->reference) {
                            return $record->reference;
                        }

                        return 'DRAFT-' . str_pad((string) $record->id, 5, '0', STR_PAD_LEFT);
                    })
                    ->badge()
                    ->color(fn(Payment $record): string => $record->reference ? 'success' : 'warning')
                    ->icon(fn(Payment $record): string => $record->reference ? 'heroicon-m-check-circle' : 'heroicon-m-pencil-square'),

                // Partner (critical for identification)
                TextColumn::make('partner.name')
                    ->label(__('payment.partner'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Payment Type (critical for understanding direction)
                TextColumn::make('payment_type')
                    ->label(__('payment.type'))
                    ->formatStateUsing(fn(PaymentType $state): string => $state->label())
                    ->badge()
                    ->color(fn(PaymentType $state): string => match ($state) {
                        PaymentType::Inbound => 'success',
                        PaymentType::Outbound => 'danger',
                    })
                    ->icons([
                        'heroicon-m-arrow-down-circle' => PaymentType::Inbound,
                        'heroicon-m-arrow-up-circle' => PaymentType::Outbound,
                    ])
                    ->searchable(),

                // Payment Method (important for categorization)
                TextColumn::make('payment_method')
                    ->label(__('payment.method'))
                    ->formatStateUsing(fn(PaymentMethod $state): string => $state->label())
                    ->badge()
                    ->color(fn(PaymentMethod $state): string => $state->color())
                    ->icon(fn(PaymentMethod $state): string => $state->icon())
                    ->searchable()
                    ->toggleable(),

                // Status (critical for workflow)
                TextColumn::make('status')
                    ->label(__('payment.status'))
                    ->formatStateUsing(fn(PaymentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn(PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Draft => 'gray',
                        PaymentStatus::Confirmed => 'warning',
                        PaymentStatus::Reconciled => 'success',
                        PaymentStatus::Canceled => 'danger',
                    })
                    ->icons([
                        'heroicon-m-pencil-square' => PaymentStatus::Draft,
                        'heroicon-m-clock' => PaymentStatus::Confirmed,
                        'heroicon-m-check-circle' => PaymentStatus::Reconciled,
                        'heroicon-m-x-circle' => PaymentStatus::Canceled,
                    ])
                    ->searchable(),

                // Payment Date (important for chronological sorting)
                TextColumn::make('payment_date')
                    ->label(__('payment.date'))
                    ->date()
                    ->sortable(),

                // Amount (critical financial information)
                MoneyColumn::make('amount')
                    ->label(__('payment.amount'))
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                // Currency (important for multi-currency)
                TextColumn::make('currency.code')
                    ->label(__('payment.currency'))
                    ->badge()
                    ->toggleable(),

                // Journal (important for categorization)
                TextColumn::make('journal.name')
                    ->label(__('payment.journal'))
                    ->sortable()
                    ->toggleable(),

                // Company (for multi-company setups)
                TextColumn::make('company.name')
                    ->label(__('payment.company'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('payment.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('payment.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (Payment $record) {
                        app(PaymentService::class)->delete($record);
                    })
                    ->visible(fn(Payment $record): bool => $record->status === PaymentStatus::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(fn(Payment $record) => app(PaymentService::class)->delete($record));
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InvoicesRelationManager::class,
            VendorBillsRelationManager::class,
            JournalEntriesRelationManager::class,
            BankStatementLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}

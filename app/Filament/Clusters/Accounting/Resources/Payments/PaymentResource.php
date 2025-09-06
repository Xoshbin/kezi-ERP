<?php

namespace App\Filament\Clusters\Accounting\Resources\Payments;


use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\CreatePayment;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\ListPayments;
use App\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\BankStatementLinesRelationManager;
use App\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\InvoicesRelationManager;
use App\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\JournalEntriesRelationManager;
use App\Filament\Clusters\Accounting\Resources\Payments\RelationManagers\VendorBillsRelationManager;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Support\TranslatableSelect;
use App\Filament\Tables\Columns\MoneyColumn;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

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
                ->description(__('payment.form.standalone_payment_description'))
                ->schema([
                    TranslatableSelect::make('journal_id', Journal::class, __('payment.form.journal_id'))
                        ->required()
                        ->columnSpan(2),
                    TranslatableSelect::make('currency_id', Currency::class, __('payment.form.currency_id'))
                        ->required()
                        ->columnSpan(2)
                        ->default(function (): ?int {
                            $tenant = Filament::getTenant();
                            return $tenant instanceof \App\Models\Company ? $tenant->currency_id : null;
                        }),
                    DatePicker::make('payment_date')
                        ->default(now())
                        ->label(__('payment.form.payment_date'))
                        ->required()
                        ->columnSpan(2),
                    TextInput::make('reference')
                        ->label(__('payment.form.reference'))
                        ->maxLength(255)
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('payment.form.payment_details'))
                ->description(__('payment.form.payment_details_description'))
                ->schema([
                    Select::make('payment_type')
                        ->label(__('payment.form.payment_type'))
                        ->options(collect(PaymentType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                        ->required()
                        ->columnSpan(2),
                    Select::make('payment_method')
                        ->label(__('payment.form.payment_method'))
                        ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                        ->required()
                        ->columnSpan(2),
                    Select::make('payment_purpose')
                        ->label(__('payment.form.payment_purpose'))
                        ->options(collect(PaymentPurpose::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                        ->required()
                        ->columnSpan(2),

                    TranslatableSelect::make('partner_id', Partner::class, __('payment.form.partner'))
                        ->required()
                        ->columnSpan(2),
                    MoneyInput::make('amount')
                        ->label(__('payment.form.amount'))
                        ->currencyField('currency_id')
                        ->required()
                        ->columnSpan(2),
                    TranslatableSelect::make('counterpart_account_id', Account::class, __('payment.form.counterpart_account'))
                        ->required()
                        ->columnSpan(2),

                ])
                ->columns(4)
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

                        return 'DRAFT-'.str_pad((string) $record->id, 5, '0', STR_PAD_LEFT);
                    })
                    ->badge()
                    ->color(fn (Payment $record): string => $record->reference ? 'success' : 'warning')
                    ->icon(fn (Payment $record): string => $record->reference ? 'heroicon-m-check-circle' : 'heroicon-m-pencil-square'),

                // Partner (critical for identification)
                TextColumn::make('partner.name')
                    ->label(__('payment.partner'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                // Payment Type (critical for understanding direction)
                TextColumn::make('payment_type')
                    ->label(__('payment.type'))
                    ->formatStateUsing(fn (PaymentType $state): string => $state->label())
                    ->badge()
                    ->color(fn (PaymentType $state): string => match ($state) {
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
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label())
                    ->badge()
                    ->color(fn (PaymentMethod $state): string => $state->color())
                    ->icon(fn (PaymentMethod $state): string => $state->icon())
                    ->searchable()
                    ->toggleable(),

                // Status (critical for workflow)
                TextColumn::make('status')
                    ->label(__('payment.status'))
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => match ($state) {
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
                    ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(fn (Payment $record) => app(PaymentService::class)->delete($record));
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

<?php

namespace App\Filament\Resources\VendorBills\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachBulkAction;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Payment;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('vendor_bill.payments_relation_manager.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('vendor_bill.payments_relation_manager.payment_details'))
                    ->schema([
                        Select::make('company_id')
                            ->relationship('company', 'name')
                            ->label(__('vendor_bill.payments_relation_manager.company'))
                            ->required()
                            ->default(fn() => $this->getOwnerRecord()->company_id),

                        Select::make('journal_id')
                            ->relationship('journal', 'name')
                            ->label(__('vendor_bill.payments_relation_manager.journal'))
                            ->required(),

                        Select::make('currency_id')
                            ->relationship('currency', 'name')
                            ->label(__('vendor_bill.payments_relation_manager.currency'))
                            ->required()
                            ->default(fn() => $this->getOwnerRecord()->currency_id),

                        DatePicker::make('payment_date')
                            ->label(__('vendor_bill.payments_relation_manager.payment_date'))
                            ->required()
                            ->default(now()),

                        TextInput::make('amount')
                            ->label(__('vendor_bill.payments_relation_manager.amount'))
                            ->required()
                            ->numeric()
                            ->step(0.01),

                        Select::make('payment_type')
                            ->label(__('vendor_bill.payments_relation_manager.payment_type'))
                            ->options([
                                PaymentType::Inbound->value => PaymentType::Inbound->label(),
                                PaymentType::Outbound->value => PaymentType::Outbound->label(),
                            ])
                            ->required()
                            ->default(PaymentType::Outbound->value),

                        TextInput::make('reference')
                            ->label(__('vendor_bill.payments_relation_manager.reference'))
                            ->maxLength(255),

                        Select::make('status')
                            ->label(__('vendor_bill.payments_relation_manager.status'))
                            ->options([
                                PaymentStatus::Draft->value => PaymentStatus::Draft->label(),
                                PaymentStatus::Confirmed->value => PaymentStatus::Confirmed->label(),
                                PaymentStatus::Reconciled->value => PaymentStatus::Reconciled->label(),
                                PaymentStatus::Canceled->value => PaymentStatus::Canceled->label(),
                            ])
                            ->required()
                            ->default(PaymentStatus::Draft->value),
                    ])
                    ->columns(2),

                Section::make(__('vendor_bill.payments_relation_manager.application_details'))
                    ->schema([
                        TextInput::make('pivot.amount_applied')
                            ->label(__('vendor_bill.payments_relation_manager.amount_applied'))
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->helperText(__('vendor_bill.payments_relation_manager.amount_applied_help')),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('payment_date')
                    ->label(__('vendor_bill.payments_relation_manager.payment_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('reference')
                    ->label(__('vendor_bill.payments_relation_manager.reference'))
                    ->searchable()
                    ->placeholder(__('vendor_bill.payments_relation_manager.no_reference')),

                MoneyColumn::make('amount')
                    ->label(__('vendor_bill.payments_relation_manager.amount'))
                    ->sortable(),

                MoneyColumn::make('pivot.amount_applied')
                    ->label(__('vendor_bill.payments_relation_manager.amount_applied'))
                    ->sortable(),

                TextColumn::make('payment_type')
                    ->label(__('vendor_bill.payments_relation_manager.payment_type'))
                    ->formatStateUsing(fn(PaymentType $state): string => $state->label())
                    ->badge()
                    ->color(fn(PaymentType $state): string => match($state) {
                        PaymentType::Inbound => 'success',
                        PaymentType::Outbound => 'danger',
                    }),

                TextColumn::make('status')
                    ->label(__('vendor_bill.payments_relation_manager.status'))
                    ->formatStateUsing(fn(PaymentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn(PaymentStatus $state): string => match($state) {
                        PaymentStatus::Draft => 'gray',
                        PaymentStatus::Confirmed => 'warning',
                        PaymentStatus::Reconciled => 'success',
                        PaymentStatus::Canceled => 'danger',
                    }),

                TextColumn::make('journal.name')
                    ->label(__('vendor_bill.payments_relation_manager.journal'))
                    ->toggleable(),

                TextColumn::make('journalEntry.id')
                    ->label(__('vendor_bill.payments_relation_manager.journal_entry'))
                    ->placeholder(__('vendor_bill.payments_relation_manager.no_journal_entry'))
                    ->toggleable(),

                TextColumn::make('bankStatementLines.description')
                    ->label(__('vendor_bill.payments_relation_manager.bank_statement_reference'))
                    ->placeholder(__('vendor_bill.payments_relation_manager.not_reconciled'))
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (!$state || strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    })
                    ->toggleable(),

                TextColumn::make('bankStatementLines.date')
                    ->label(__('vendor_bill.payments_relation_manager.reconciliation_date'))
                    ->date()
                    ->placeholder(__('vendor_bill.payments_relation_manager.not_reconciled'))
                    ->toggleable(),

                IconColumn::make('is_reconciled')
                    ->label(__('vendor_bill.payments_relation_manager.reconciliation_status'))
                    ->getStateUsing(fn(Payment $record): bool => $record->status === PaymentStatus::Reconciled)
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('vendor_bill.payments_relation_manager.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('vendor_bill.payments_relation_manager.filter_status'))
                    ->options([
                        PaymentStatus::Draft->value => PaymentStatus::Draft->label(),
                        PaymentStatus::Confirmed->value => PaymentStatus::Confirmed->label(),
                        PaymentStatus::Reconciled->value => PaymentStatus::Reconciled->label(),
                        PaymentStatus::Canceled->value => PaymentStatus::Canceled->label(),
                    ]),

                SelectFilter::make('payment_type')
                    ->label(__('vendor_bill.payments_relation_manager.filter_payment_type'))
                    ->options([
                        PaymentType::Inbound->value => PaymentType::Inbound->label(),
                        PaymentType::Outbound->value => PaymentType::Outbound->label(),
                    ]),

                TernaryFilter::make('is_reconciled')
                    ->label(__('vendor_bill.payments_relation_manager.filter_reconciliation_status'))
                    ->placeholder(__('vendor_bill.payments_relation_manager.filter_all_reconciliation'))
                    ->trueLabel(__('vendor_bill.payments_relation_manager.filter_reconciled'))
                    ->falseLabel(__('vendor_bill.payments_relation_manager.filter_not_reconciled'))
                    ->queries(
                        true: fn (Builder $query) => $query->where('status', PaymentStatus::Reconciled),
                        false: fn (Builder $query) => $query->where('status', '!=', PaymentStatus::Reconciled),
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('vendor_bill.payments_relation_manager.create_payment'))
                    ->mutateDataUsing(function (array $data): array {
                        $data['paid_to_from_partner_id'] = $this->getOwnerRecord()->vendor_id;
                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DetachAction::make()
                    ->label(__('vendor_bill.payments_relation_manager.detach'))
                    ->visible(fn(Payment $record): bool => $record->status === PaymentStatus::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->label(__('vendor_bill.payments_relation_manager.detach_selected')),
                ]),
            ])
            ->defaultSort('payment_date', 'desc');
    }
}

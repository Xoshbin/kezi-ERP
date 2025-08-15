<?php

namespace App\Filament\Resources\Payments;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Payments\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\Payments\RelationManagers\VendorBillsRelationManager;
use App\Filament\Resources\Payments\RelationManagers\JournalEntriesRelationManager;
use App\Filament\Resources\Payments\RelationManagers\BankStatementLinesRelationManager;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use Filament\Forms;
use Filament\Tables;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\VendorBill;
use Filament\Tables\Table;
use App\Services\PaymentService;
use Filament\Resources\Resource;
use App\Enums\Sales\InvoiceStatus;
use App\Enums\Payments\PaymentType;
use App\Enums\Payments\PaymentStatus;
use Filament\Forms\Components\Repeater;
use App\Enums\Purchases\VendorBillStatus;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Filament\Support\TranslatableSelect;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.banking_cash');
    }

    public static function getNavigationLabel(): string
    {
        return __('payment.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('payment.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payment.model_plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TranslatableSelect::make('journal_id', \App\Models\Journal::class, __('payment.form.journal_id'))
                        ->required(),
                    TranslatableSelect::make('currency_id', \App\Models\Currency::class, __('payment.form.currency_id'))
                        ->required()
                        ->live()
                        ->default(fn() => \Filament\Facades\Filament::getTenant()?->currency_id),
                    DatePicker::make('payment_date')
                        ->default(now())
                        ->label(__('payment.form.payment_date'))
                        ->required(),
                    TextInput::make('reference')
                        ->label(__('payment.form.reference'))
                        ->maxLength(255),

                    // Read-only fields derived by the Action
                    MoneyInput::make('amount')
                        ->label(__('payment.form.amount'))
                        ->currencyField('currency_id')
                        ->readOnly(),
                    Select::make('payment_type')
                        ->label(__('payment.form.payment_type'))
                        ->options(collect(PaymentType::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('status')
                        ->label(__('payment.form.status'))
                        ->options(collect(PaymentStatus::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(2),

            Section::make(__('payment.form.document_links'))
                ->schema([
                    Repeater::make('document_links')
                        ->label(__('payment.form.document_links'))
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->disabled(fn (?Payment $record) => $record && $record->status !== PaymentStatus::Draft)
                        ->schema([
                            Select::make('document_type')
                                ->label(__('payment.form.document_type'))
                                ->options([
                                    'invoice' => __('payment.form.document_type.invoice'),
                                    'vendor_bill' => __('payment.form.document_type.vendor_bill'),
                                ])
                                ->required()
                                ->live()
                                ->columnSpan(1),
                            Select::make('document_id')
                                ->label(__('payment.form.document_id'))
                                ->options(function (Get $get) {
                                    $type = $get('document_type');
                                    if ($type === 'invoice') {
                                        return Invoice::where('status', InvoiceStatus::Posted)->pluck('invoice_number', 'id');
                                    }
                                    if ($type === 'vendor_bill') {
                                        return VendorBill::where('status', VendorBillStatus::Posted)->pluck('bill_reference', 'id');
                                    }
                                    return [];
                                })
                                ->searchable()
                                ->required()
                                ->columnSpan(1),
                            MoneyInput::make('amount_applied')
                                ->label(__('payment.form.amount_applied'))
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(1),
                        ])
                        ->columns(3)
                        ->afterStateUpdated(function (Get $get, callable $set) {
                            $links = $get('document_links') ?? [];
                            $total = 0;
                            foreach ($links as $link) {
                                $total += (float)($link['amount_applied'] ?? 0);
                            }
                            $set('amount', $total);
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('payment.table.company.name'))
                    ->sortable(),
                TextColumn::make('journal.name')
                    ->label(__('payment.table.journal.name'))
                    ->sortable(),
                TextColumn::make('currency.name')
                    ->label(__('payment.table.currency.name'))
                    ->sortable(),
                TextColumn::make('partner.name')
                    ->label(__('payment.table.partner.name'))
                    ->sortable(),
                TextColumn::make('payment_date')
                    ->label(__('payment.table.payment_date'))
                    ->date()
                    ->sortable(),
                MoneyColumn::make('amount')
                    ->label(__('payment.table.amount'))
                    ->sortable(),
                TextColumn::make('payment_type')
                    ->label(__('payment.table.payment_type'))
                    ->formatStateUsing(fn(PaymentType $state): string => $state->label())
                    ->badge()
                    ->color(fn(PaymentType $state): string => match($state) {
                        PaymentType::Inbound => 'success',
                        PaymentType::Outbound => 'danger',
                    })
                    ->searchable(),

                TextColumn::make('status')
                    ->label(__('payment.table.status'))
                    ->formatStateUsing(fn(PaymentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn(PaymentStatus $state): string => match($state) {
                        PaymentStatus::Draft => 'gray',
                        PaymentStatus::Confirmed => 'warning',
                        PaymentStatus::Reconciled => 'success',
                        PaymentStatus::Canceled => 'danger',
                    })
                    ->searchable(),
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
                // Tables\Actions\Action::make('cancel')
                //     ->label('Cancel Payment')
                //     ->color('danger')
                //     ->requiresConfirmation()
                //     // This action is only visible for confirmed, but not yet reconciled, payments
                //     ->visible(fn(\App\Models\Payment $record): bool => $record->status === 'Confirmed')
                //     ->action(function (\App\Models\Payment $record, \App\Services\PaymentService $paymentService) {
                //         try {
                //             $paymentService->cancel($record, auth()->user(), 'Payment cancelled via table action');
                //             \Filament\Notifications\Notification::make()
                //                 ->title('Payment Cancelled Successfully')
                //                 ->success()
                //                 ->send();
                //         } catch (\Exception $e) {
                //             \Filament\Notifications\Notification::make()
                //                 ->title('Error Cancelling Payment')
                //                 ->body($e->getMessage())
                //                 ->danger()
                //                 ->send();
                //         }
                //     }),
                // Action::make('confirm')
                //     ->label(__('payment.action.confirm.label'))
                //     ->action(function (Payment $record) {
                //         $paymentService = app(PaymentService::class);
                //         try {
                //             $paymentService->confirm($record, Auth::user());
                //             Notification::make()
                //                 ->title(__('payment.action.confirm.notification.success'))
                //                 ->success()
                //                 ->send();
                //         } catch (\Exception $e) {
                //             Notification::make()
                //                 ->title(__('payment.action.confirm.notification.error'))
                //                 ->body($e->getMessage())
                //                 ->danger()
                //                 ->send();
                //         }
                //     })
                //     ->requiresConfirmation()
                //     ->visible(fn(Payment $record) => $record->status === 'Draft'),
                DeleteAction::make()
                    ->action(function (Payment $record) {
                        app(PaymentService::class)->delete($record);
                    })
                    // Make the button disappear if deletion is not allowed
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

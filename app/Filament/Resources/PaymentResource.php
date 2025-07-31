<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\VendorBill;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): ?string
    {
        return __('payment.navigation_group');
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')->relationship('company', 'name')->required()->label(__('payment.form.company_id')),
                Forms\Components\Select::make('journal_id')->relationship('journal', 'name')->required()->label(__('payment.form.journal_id')),
                Forms\Components\Select::make('currency_id')->relationship('currency', 'name')->required()->label(__('payment.form.currency_id')),
                Forms\Components\DatePicker::make('payment_date')->required()->label(__('payment.form.payment_date')),
                Forms\Components\TextInput::make('reference')->maxLength(255)->label(__('payment.form.reference')),

                // Read-only fields derived by the Action
                Forms\Components\TextInput::make('amount')->numeric()->readOnly()->label(__('payment.form.amount')),
                Forms\Components\Select::make('payment_type')->options(Payment::getTypes())->disabled()->label(__('payment.form.payment_type')),
                Forms\Components\Select::make('status')->options(Payment::getStatuses())->disabled()->label(__('payment.form.status')),

                Repeater::make('document_links')
                    ->label(__('payment.form.document_links'))
                    ->schema([
                        Forms\Components\Select::make('document_type')
                            ->label(__('payment.form.document_type'))
                            ->options([
                                'invoice' => __('payment.form.document_type.invoice'),
                                'vendor_bill' => __('payment.form.document_type.vendor_bill'),
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('document_id')
                            ->label(__('payment.form.document_id'))
                            ->options(function (Get $get) {
                                $type = $get('document_type');
                                if ($type === 'invoice') {
                                    return Invoice::where('status', Invoice::STATUS_POSTED)->pluck('invoice_number', 'id');
                                }
                                if ($type === 'vendor_bill') {
                                    return VendorBill::where('status', VendorBill::STATUS_POSTED)->pluck('bill_reference', 'id');
                                }
                                return [];
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('amount_applied')->numeric()->required()->label(__('payment.form.amount_applied')),
                    ])->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, callable $set) {
                        $links = $get('document_links') ?? [];
                        $total = 0;
                        foreach ($links as $link) {
                            $total += (float)($link['amount_applied'] ?? 0);
                        }
                        $set('amount', $total);
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('payment.table.company.name'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('journal.name')
                    ->label(__('payment.table.journal.name'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->label(__('payment.table.currency.name'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('partner.name')
                    ->label(__('payment.table.partner.name'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label(__('payment.table.payment_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('payment.table.amount'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label(__('payment.table.payment_type'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('payment.table.status'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('payment.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('payment.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel Payment')
                    ->color('danger')
                    ->requiresConfirmation()
                    // This action is only visible for confirmed, but not yet reconciled, payments
                    ->visible(fn(\App\Models\Payment $record): bool => $record->status === 'Confirmed')
                    ->action(function (\App\Models\Payment $record, \App\Services\PaymentService $paymentService) {
                        try {
                            $paymentService->cancel($record, auth()->user());
                            \Filament\Notifications\Notification::make()
                                ->title('Payment Cancelled Successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error Cancelling Payment')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('confirm')
                    ->label(__('payment.action.confirm.label'))
                    ->action(function (Payment $record) {
                        $paymentService = app(PaymentService::class);
                        try {
                            $paymentService->confirm($record, Auth::user());
                            Notification::make()
                                ->title(__('payment.action.confirm.notification.success'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('payment.action.confirm.notification.error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn(Payment $record) => $record->status === 'Draft'),
                Tables\Actions\DeleteAction::make()
                    ->action(function (Payment $record) {
                        app(PaymentService::class)->delete($record);
                    })
                    // Make the button disappear if deletion is not allowed
                    ->visible(fn(Payment $record): bool => $record->status === Payment::STATUS_DRAFT),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(fn(Payment $record) => app(PaymentService::class)->delete($record));
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InvoicesRelationManager::class,
            RelationManagers\VendorBillsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')->relationship('company', 'name')->required(),
                Forms\Components\Select::make('journal_id')->relationship('journal', 'name')->required(),
                Forms\Components\Select::make('currency_id')->relationship('currency', 'name')->required(),
                Forms\Components\DatePicker::make('payment_date')->required(),
                Forms\Components\TextInput::make('reference')->maxLength(255),

                // Read-only fields derived by the Action
                Forms\Components\TextInput::make('amount')->numeric()->readOnly()->label('Total Amount'),
                Forms\Components\Select::make('payment_type')->options(Payment::getTypes())->disabled(),
                Forms\Components\Select::make('status')->options(Payment::getStatuses())->disabled(),

                Repeater::make('document_links')
                    ->schema([
                        Forms\Components\Select::make('document_type')
                            ->options([
                                'invoice' => 'Invoice',
                                'vendor_bill' => 'Vendor Bill',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('document_id')
                            ->label('Document')
                            ->options(function (Get $get) {
                                $type = $get('document_type');
                                if ($type === 'invoice') {
                                    return Invoice::where('status', Invoice::TYPE_POSTED)->pluck('invoice_number', 'id');
                                }
                                if ($type === 'vendor_bill') {
                                    return VendorBill::where('status', VendorBill::TYPE_POSTED)->pluck('bill_reference', 'id');
                                }
                                return [];
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('amount_applied')->numeric()->required(),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('journal.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('partner.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('confirm')
                    ->action(function (Payment $record) {
                        $paymentService = new PaymentService();
                        try {
                            $paymentService->confirm($record, Auth::user());
                            Notification::make()
                                ->title('Payment confirmed successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error confirming payment')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn(Payment $record) => $record->status === 'Draft'),
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

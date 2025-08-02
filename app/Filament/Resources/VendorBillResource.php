<?php

namespace App\Filament\Resources;

use App\Models\Account;
use App\Models\AnalyticAccount;
use App\Models\Product;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\VendorBill;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Services\VendorBillService;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\VendorBillResource\Pages;
use App\Filament\Resources\VendorBillResource\RelationManagers;
use Filament\Forms\Components\Repeater;
use App\Models\Currency;
use App\Models\Tax;
use Illuminate\Support\Facades\Auth;

use App\Rules\NotInLockedPeriod;

class VendorBillResource extends Resource
{
    protected static ?string $model = VendorBill::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('vendor_bill.company'))
                    ->required(),
                Forms\Components\Select::make('vendor_id')
                    ->relationship('vendor', 'name')
                    ->label(__('vendor_bill.vendor'))
                    ->required(),
                Forms\Components\Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->label(__('vendor_bill.currency'))
                    ->required(),
                Forms\Components\TextInput::make('bill_reference')
                    ->label(__('vendor_bill.bill_reference'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('bill_date')
                    ->label(__('vendor_bill.bill_date'))
                    ->required()
                    ->rules([new NotInLockedPeriod()]),
                Forms\Components\DatePicker::make('accounting_date')
                    ->label(__('vendor_bill.accounting_date'))
                    ->required()
                    ->rules([new NotInLockedPeriod()]),
                Forms\Components\DatePicker::make('due_date')
                    ->label(__('vendor_bill.due_date')),
                Forms\Components\Select::make('status')
                    ->label(__('vendor_bill.status'))
                    ->options(VendorBill::getStatuses())
                    ->disabled()
                    ->dehydrated(false),

                Repeater::make('lines')
                    ->label(__('vendor_bill.lines'))
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label(__('vendor_bill.product'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Product::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('description', $product->name);
                                        $set('unit_price', $product->unit_price?->getAmount()->toFloat());
                                        $set('expense_account_id', $product->expense_account_id);
                                    }
                                }
                            })
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('description')->label(__('vendor_bill.description'))->maxLength(255)->required()->columnSpan(2),
                        Forms\Components\TextInput::make('quantity')->label(__('vendor_bill.quantity'))->required()->numeric()->default(1)->columnSpan(1),
                        Forms\Components\TextInput::make('unit_price')->label(__('vendor_bill.unit_price'))->required()->numeric()->columnSpan(1),
                        Forms\Components\Select::make('tax_id')
                            ->label(__('vendor_bill.tax'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Tax::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Tax::find($value)?->name)
                            ->columnSpan(1),
                        Forms\Components\Select::make('expense_account_id')
                            ->label(__('vendor_bill.expense_account'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Account::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Account::find($value)?->name)
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\Select::make('analytic_account_id')
                            ->label(__('vendor_bill.analytic_account'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => AnalyticAccount::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => AnalyticAccount::find($value)?->name)
                            ->columnSpan(2),
                    ])
                    ->columns(5)
                    ->columnSpanFull()
                    ,

                Forms\Components\TextInput::make('total_amount')
                    ->label(__('vendor_bill.total_amount'))
                    ->numeric()
                    ->readOnly()
                    ->prefix(fn (callable $get) => Currency::find($get('currency_id'))?->symbol),
                Forms\Components\TextInput::make('total_tax')
                    ->label(__('vendor_bill.total_tax'))
                    ->numeric()
                    ->readOnly()
                    ->prefix(fn (callable $get) => Currency::find($get('currency_id'))?->symbol),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('vendor_bill.company'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label(__('vendor_bill.vendor'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->label(__('vendor_bill.currency'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('journalEntry.id')
                    ->label(__('vendor_bill.journal_entry_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bill_reference')
                    ->label(__('vendor_bill.bill_reference'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('bill_date')
                    ->label(__('vendor_bill.bill_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('accounting_date')
                    ->label(__('vendor_bill.accounting_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('vendor_bill.due_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('vendor_bill.status'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('vendor_bill.total_amount'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_tax')
                    ->label(__('vendor_bill.total_tax'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('posted_at')
                    ->label(__('vendor_bill.posted_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('vendor_bill.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('vendor_bill.updated_at'))
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
                    ->label(__('vendor_bill.confirm'))
                    ->action(function (VendorBill $record) {
                        $vendorBillService = app(VendorBillService::class);
                        try {
                            $vendorBillService->confirm($record, Auth::user());
                            Notification::make()
                                ->title(__('vendor_bill.notification_confirm_success'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('vendor_bill.notification_confirm_error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn(VendorBill $record) => $record->status === VendorBill::STATUS_DRAFT),
                Action::make('resetToDraft')
                    ->label(__('vendor_bill.reset_to_draft'))
                    ->action(function (VendorBill $record, array $data) {
                        $user = Auth::user();
                        $vendorBillService = app(VendorBillService::class);
                        try {
                            $vendorBillService->resetToDraft($record, $user, $data['reason']);
                            Notification::make()
                                ->title(__('vendor_bill.notification_reset_success'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('vendor_bill.notification_reset_error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->form([
                        Forms\Components\Textarea::make('reason')->label(__('vendor_bill.reason'))->required(),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn(VendorBill $record) => $record->status === 'Posted'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VendorBillLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorBills::route('/'),
            'create' => Pages\CreateVendorBill::route('/create'),
            'edit' => Pages\EditVendorBill::route('/{record}/edit'),
        ];
    }
}

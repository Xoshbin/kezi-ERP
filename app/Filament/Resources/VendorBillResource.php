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
                    ->required(),
                Forms\Components\Select::make('vendor_id')
                    ->relationship('vendor', 'name')
                    ->required(),
                Forms\Components\Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->required(),
                // journal_entry_id is system-assigned, so it's removed from the form.
                Forms\Components\TextInput::make('bill_reference')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('bill_date')
                    ->required(),
                Forms\Components\DatePicker::make('accounting_date')
                    ->required(),
                Forms\Components\DatePicker::make('due_date'),
                Forms\Components\Select::make('status')
                    ->options(VendorBill::getTypes())
                    ->required()
                    // The status is now always disabled.
                    // State changes are handled ONLY by the header actions.
                    ->disabled()
                    // Tell Filament to not even try saving this field's value.
                    ->dehydrated(false),

                Repeater::make('lines')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Product::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('description', $product->name);
                                        $set('unit_price', $product->unit_price->getAmount()->toFloat());
                                        $set('expense_account_id', $product->expense_account_id);
                                    }
                                }
                            })
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('description')->maxLength(255)->required()->columnSpan(2),
                        Forms\Components\TextInput::make('quantity')->required()->numeric()->default(1)->columnSpan(1),
                        Forms\Components\TextInput::make('unit_price')->required()->numeric()->columnSpan(1),
                        Forms\Components\Select::make('tax_id')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Tax::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Tax::find($value)?->name)
                            ->columnSpan(1),
                        Forms\Components\Select::make('expense_account_id')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Account::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Account::find($value)?->name)
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\Select::make('analytic_account_id')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => AnalyticAccount::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => AnalyticAccount::find($value)?->name)
                            ->columnSpan(2),
                    ])
                    ->columns(5) // Adjusted column count for better layout.
                    ->columnSpanFull()
                    // IMPROVEMENT 2: Correct real-time calculation of totals.
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        $lines = $get('lines') ?? [];
                        $totalAmount = 0;
                        $totalTax = 0;

                        foreach ($lines as $line) {
                            $quantity = (float)($line['quantity'] ?? 0);
                            $unitPrice = (float)($line['unit_price'] ?? 0);
                            $subtotal = $quantity * $unitPrice;

                            $lineTax = 0;
                            if (!empty($line['tax_id'])) {
                                $tax = Tax::find($line['tax_id']);
                                if ($tax) {
                                    // Assumes tax rate is stored as a decimal (e.g., 0.10 for 10%).
                                    $lineTax = $subtotal * $tax->rate;
                                }
                            }
                            $totalTax += $lineTax;
                            $totalAmount += $subtotal + $lineTax;
                        }
                        // Update the read-only total fields at the bottom of the form.
                        $set('../../total_amount', $totalAmount);
                        $set('../../total_tax', $totalTax);
                    })
                    ->live(onBlur: true), // The 'live' is what enables the reactivity.

                Forms\Components\TextInput::make('total_amount')
                    ->numeric()
                    ->readOnly()
                    ->prefix(fn (callable $get) => Currency::find($get('currency_id'))?->symbol),
                Forms\Components\TextInput::make('total_tax')
                    ->numeric()
                    ->readOnly()
                    ->prefix(fn (callable $get) => Currency::find($get('currency_id'))?->symbol),
                // These fields are for system logging and should not be on the form.
                // Forms\Components\DateTimePicker::make('posted_at'),
                // Forms\Components\TextInput::make('reset_to_draft_log'),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('journalEntry.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bill_reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bill_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('accounting_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_tax')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('posted_at')
                    ->dateTime()
                    ->sortable(),
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
                    ->action(function (VendorBill $record) {
                        $vendorBillService = app(VendorBillService::class);
                        try {
                            $vendorBillService->confirm($record, Auth::user());
                            Notification::make()
                                ->title('Vendor bill confirmed successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error: Could not confirm vendor bill')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn(VendorBill $record) => $record->status === VendorBill::TYPE_DRAFT),
                Action::make('resetToDraft')
                    ->action(function (VendorBill $record, array $data) {
                        $user = Auth::user();
                        $vendorBillService = app(VendorBillService::class);
                        try {
                            $vendorBillService->resetToDraft($record, $user, $data['reason']);
                            Notification::make()
                                ->title('Vendor bill reset to draft successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error: Could not reset vendor bill')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->form([
                        Forms\Components\Textarea::make('reason')->required(),
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

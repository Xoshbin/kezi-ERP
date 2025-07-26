<?php

namespace App\Filament\Resources;

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
                Forms\Components\Select::make('journal_entry_id')
                    ->relationship('journalEntry', 'id'),
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
                    ->default(VendorBill::TYPE_DRAFT),
                Repeater::make('lines')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('description')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('unit_price')
                            ->required()
                            ->numeric()
                            ->columnSpan(1),
                        Forms\Components\Select::make('tax_id')
                            ->relationship('tax', 'name')
                            ->searchable()
                            ->columnSpan(1),
                        Forms\Components\Select::make('expense_account_id')
                            ->relationship('expenseAccount', 'name')
                            ->searchable()
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\Select::make('analytic_account_id')
                            ->relationship('analyticAccount', 'name')
                            ->searchable()
                            ->columnSpan(2),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        // Update total_amount when any line changes
                        $totalDebit = collect($get('lines'))->sum('debit');
                        $totalCredit = collect($get('lines'))->sum('credit');

                        // Set the total_amount to the sum of debits (should equal credits in a balanced entry)
                        $set('../../total_amount', $totalDebit);
                    })
                    ->live(onBlur: true),
                Forms\Components\TextInput::make('total_amount')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('total_tax')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\DateTimePicker::make('posted_at'),
                Forms\Components\TextInput::make('reset_to_draft_log'),
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

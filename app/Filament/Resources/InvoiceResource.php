<?php

namespace App\Filament\Resources;

use App\Models\Tax;
use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Services\InvoiceService;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\InvoiceResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\InvoiceResource\RelationManagers;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required(),
                Forms\Components\Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->required(),
                Forms\Components\Select::make('journal_entry_id')
                    ->relationship('journalEntry', 'id'),
                Forms\Components\Select::make('fiscal_position_id')
                    ->relationship('fiscalPosition', 'name'),
                Forms\Components\TextInput::make('invoice_number')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('invoice_date')
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(Invoice::getTypes())
                    ->required()
                    ->default(Invoice::TYPE_DRAFT),
                Repeater::make('invoiceLines')
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
                        Forms\Components\Select::make('income_account_id')
                            ->relationship('incomeAccount', 'name')
                            ->searchable()
                            ->required()
                            ->columnSpan(2)
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        // Update total_amount when any line changes
                        $totalDebit = collect($get('invoiceLines'))->sum('debit');
                        $totalCredit = collect($get('invoiceLines'))->sum('credit');

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
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('journalEntry.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fiscalPosition.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')
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
                    ->action(function (Invoice $record) {
                        $invoiceService = new InvoiceService();
                        try {
                            $invoiceService->confirm($record, auth()->user());
                            Notification::make()
                                ->title('Invoice confirmed successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error confirming invoice')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn(Invoice $record) => $record->status === Invoice::TYPE_DRAFT),
                Action::make('resetToDraft')
                    ->action(function (Invoice $record, array $data) {
                        $invoiceService = new InvoiceService();
                        try {
                            $invoiceService->resetToDraft($record, auth()->user(), $data['reason']);
                            Notification::make()
                                ->title('Invoice reset to draft successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error resetting invoice to draft')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->form([
                        Forms\Components\Textarea::make('reason')->required(),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn(Invoice $record) => $record->status === 'Posted'),
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
            RelationManagers\InvoiceLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}

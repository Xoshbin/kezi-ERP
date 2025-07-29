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
use App\Models\Account;
use App\Models\Product;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')->relationship('company', 'name')->required(),
                Forms\Components\Select::make('customer_id')->relationship('customer', 'name')->required(),
                Forms\Components\Select::make('currency_id')->relationship('currency', 'name')->required(),
                Forms\Components\Select::make('fiscal_position_id')->relationship('fiscalPosition', 'name'),
                Forms\Components\DatePicker::make('invoice_date')->required(),
                Forms\Components\DatePicker::make('due_date')->required(),
                Forms\Components\Select::make('status')
                    ->options(Invoice::getTypes())
                    ->disabled()
                    ->dehydrated(false),

                Repeater::make('invoiceLines')
                    // ->relationship() // REMOVED
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
                                        $set('description', $product->description);
                                        $set('unit_price', $product->unit_price->getAmount()->toFloat());
                                        $set('income_account_id', $product->income_account_id);
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
                        Forms\Components\Select::make('income_account_id')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Account::where('type', 'Income')->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Account::find($value)?->name)
                            ->required()
                            ->columnSpan(2),
                    ])
                    ->columns(5)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('total_amount')->numeric()->readOnly()->prefix(fn (callable $get) => Currency::find($get('currency_id'))?->symbol),
                Forms\Components\TextInput::make('total_tax')->numeric()->readOnly()->prefix(fn (callable $get) => Currency::find($get('currency_id'))?->symbol),
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

<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\MoneyInput;
use App\Models\Tax;
use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use App\Models\Currency;
use App\Enums\Sales\InvoiceStatus;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Services\InvoiceService;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\InvoiceResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Account;
use App\Models\Product;
use App\Models\Company;
use App\Rules\NotInLockedPeriod;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.sales_purchases');
    }

    public static function getModelLabel(): string
    {
        return __('invoice.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('invoice.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('invoice.plural_label');
    }

    public static function form(Form $form): Form
    {
        $company = Company::first();

        return $form->schema([
            Section::make()
                ->schema([
                    Forms\Components\Select::make('company_id')
                        ->relationship('company', 'name')
                        ->label(__('invoice.company'))
                        ->required()
                        ->live()
                        ->default($company?->id)
                        ->afterStateUpdated(function (callable $set, $state) {
                            $company = Company::find($state);
                            if ($company) {
                                $set('currency_id', $company->currency_id);
                            }
                        }),
                    Forms\Components\Select::make('customer_id')
                        ->relationship('customer', 'name')
                        ->label(__('invoice.customer'))
                        ->required(),
                    Forms\Components\Select::make('currency_id')
                        ->relationship('currency', 'name')
                        ->label(__('invoice.currency'))
                        ->required()
                        ->live()
                        ->default($company?->currency_id),
                    Forms\Components\Select::make('fiscal_position_id')
                        ->relationship('fiscalPosition', 'name')
                        ->label(__('invoice.fiscal_position')),
                    Forms\Components\DatePicker::make('invoice_date')
                        ->label(__('invoice.invoice_date'))
                        ->default(now())
                        ->required()
                        ->rules([new NotInLockedPeriod($company)]),
                    Forms\Components\DatePicker::make('due_date')
                        ->label(__('invoice.due_date'))
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label(__('invoice.status'))
                        ->options(
                            collect(InvoiceStatus::cases())
                                ->mapWithKeys(fn (InvoiceStatus $status) => [$status->value => $status->label()])
                        )
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(2),

            Section::make(__('invoice.invoice_lines'))
                ->schema([
                    Forms\Components\Repeater::make('invoiceLines')
                        ->label(__('invoice.invoice_lines'))
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label(__('invoice.product'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array => Product::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->name)
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('description', $product->description);
                                            $set('unit_price', $product->unit_price);
                                            $set('income_account_id', $product->income_account_id);
                                        }
                                    }
                                })
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('description')
                                ->label(__('invoice.description'))
                                ->maxLength(255)
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')
                                ->label(__('invoice.quantity'))
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->columnSpan(1),
                            MoneyInput::make('unit_price')
                                ->label(__('invoice.unit_price'))
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(1),
                            Forms\Components\Select::make('tax_id')
                                ->label(__('invoice.tax'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array =>
                                    Tax::whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.' . app()->getLocale() . '"))) LIKE ?', ['%' . strtolower($search) . '%'])
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($tax) => [$tax->id => $tax->getTranslation('name', app()->getLocale())])
                                        ->toArray()
                                )
                                ->getOptionLabelUsing(fn($value): ?string => Tax::find($value)?->getTranslation('name', app()->getLocale()))
                                ->columnSpan(1),
                            Forms\Components\Select::make('income_account_id')
                                ->label(__('invoice.income_account'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array =>
                                    Account::where('type', 'Income')
                                        ->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.' . app()->getLocale() . '"))) LIKE ?', ['%' . strtolower($search) . '%'])
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($account) => [$account->id => $account->getTranslation('name', app()->getLocale())])
                                        ->toArray()
                                )
                                ->getOptionLabelUsing(fn($value): ?string => Account::find($value)?->getTranslation('name', app()->getLocale()))
                                ->required()
                                ->columnSpan(2),
                        ])
                        ->columns(5)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('invoice.company_name'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('invoice.customer_name'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->label(__('invoice.currency_name'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('journalEntry.id')
                    ->label(__('invoice.journal_entry'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fiscalPosition.name')
                    ->label(__('invoice.fiscal_position_name'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label(__('invoice.invoice_number'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label(__('invoice.invoice_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('invoice.due_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('invoice.status'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('invoice.total_amount'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_tax')
                    ->label(__('invoice.total_tax'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('posted_at')
                    ->label(__('invoice.posted_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('invoice.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('invoice.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Action::make('viewPdf')
                        ->label(__('View PDF'))
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn (Invoice $record) => route('invoices.pdf', $record))
                        ->openUrlInNewTab(),

                    Action::make('downloadPdf')
                        ->label(__('Download PDF'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn (Invoice $record) => route('invoices.pdf.download', $record)),
                ])
                    ->label(__('PDF'))
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->button(),
                Action::make('confirm')
                    ->label(__('invoice.confirm'))
                    ->action(function (Invoice $record) {
                        $invoiceService = app(InvoiceService::class);
                        try {
                            $invoiceService->confirm($record, Auth::user());
                            Notification::make()
                                ->title(__('invoice.invoice_confirmed_successfully'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('invoice.error_confirming_invoice'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn(Invoice $record) => $record->status === InvoiceStatus::Draft),
                Action::make('resetToDraft')
                    ->label(__('invoice.reset_to_draft'))
                    ->action(function (Invoice $record, array $data) {
                        $invoiceService = app(InvoiceService::class);
                        try {
                            $invoiceService->resetToDraft($record, Auth::user(), $data['reason']);
                            Notification::make()
                                ->title(__('invoice.invoice_reset_to_draft_successfully'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('invoice.error_resetting_invoice_to_draft'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->form([
                        Forms\Components\Textarea::make('reason')->label(__('invoice.reason'))->required(),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn(Invoice $record) => $record->status === InvoiceStatus::Posted),
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

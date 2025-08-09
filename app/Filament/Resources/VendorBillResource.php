<?php

namespace App\Filament\Resources;

use App\Enums\Purchases\VendorBillStatus;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\VendorBillResource\Pages;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Account;
use App\Models\AnalyticAccount;
use App\Models\Company;
use App\Models\Product;
use App\Models\Tax;
use App\Models\VendorBill;
use App\Rules\NotInLockedPeriod;
use App\Services\VendorBillService;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class VendorBillResource extends Resource
{
    protected static ?string $model = VendorBill::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.sales_purchases');
    }

    public static function getModelLabel(): string
    {
        return __('vendor_bill.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('vendor_bill.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('vendor_bill.plural_label');
    }

    public static function form(Form $form): Form
    {
        $company = Company::first();

        return $form->schema([
            Section::make()
                ->schema([
                    Forms\Components\Select::make('company_id')
                        ->relationship('company', 'name')
                        ->label(__('vendor_bill.company'))
                        ->required()
                        ->live()
                        ->default($company?->id)
                        ->afterStateUpdated(function (callable $set, $state) {
                            $company = Company::find($state);
                            if ($company) {
                                $set('currency_id', $company->currency_id);
                            }
                        }),
                    Forms\Components\Select::make('vendor_id')
                        ->relationship('vendor', 'name')
                        ->label(__('vendor_bill.vendor'))
                        ->required(),
                    Forms\Components\Select::make('currency_id')
                        ->relationship('currency', 'name')
                        ->label(__('vendor_bill.currency'))
                        ->required()
                        ->live()
                        ->default($company?->currency_id),
                    Forms\Components\TextInput::make('bill_reference')
                        ->label(__('vendor_bill.bill_reference'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\DatePicker::make('bill_date')
                        ->label(__('vendor_bill.bill_date'))
                        ->required()
                        ->rules([new NotInLockedPeriod($company)]),
                    Forms\Components\DatePicker::make('accounting_date')
                        ->label(__('vendor_bill.accounting_date'))
                        ->required()
                        ->rules([new NotInLockedPeriod()]),
                    Forms\Components\DatePicker::make('due_date')
                        ->label(__('vendor_bill.due_date')),
                    Forms\Components\Select::make('status')
                        ->label(__('vendor_bill.status'))
                        ->options(
                            collect(VendorBillStatus::cases())
                                ->mapWithKeys(fn (VendorBillStatus $status) => [$status->value => $status->label()])
                        )
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(2),

            Section::make(__('vendor_bill.lines'))
                ->schema([
                    Forms\Components\Repeater::make('lines')
                        ->label(__('vendor_bill.lines'))
                        ->live()
                        ->reorderable(true)
                        ->minItems(1)
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label(__('vendor_bill.product'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array => Product::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->name)
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('description', $product->name);
                                            $set('unit_price', $product->unit_price);
                                            $set('expense_account_id', $product->expense_account_id);
                                        }
                                    }
                                })
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('description')
                                ->label(__('vendor_bill.description'))
                                ->maxLength(255)
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')
                                ->label(__('vendor_bill.quantity'))
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->columnSpan(1),
                            MoneyInput::make('unit_price')
                                ->label(__('vendor_bill.unit_price'))
                                ->currencyField('../../currency_id')
                                ->required()
                                ->columnSpan(1),
                            Forms\Components\Select::make('tax_id')
                                ->label(__('vendor_bill.tax'))
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
                            Forms\Components\Select::make('expense_account_id')
                                ->label(__('vendor_bill.expense_account'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array =>
                                    Account::whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.' . app()->getLocale() . '"))) LIKE ?', ['%' . strtolower($search) . '%'])
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn($account) => [$account->id => $account->getTranslation('name', app()->getLocale())])
                                        ->toArray()
                                )
                                ->getOptionLabelUsing(fn($value): ?string => Account::find($value)?->getTranslation('name', app()->getLocale()))
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\Select::make('analytic_account_id')
                                ->label(__('vendor_bill.analytic_account'))
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search): array => AnalyticAccount::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                                ->getOptionLabelUsing(fn($value): ?string => AnalyticAccount::find($value)?->name)
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
                    ->label(__('vendor_bill.company'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label(__('vendor_bill.vendor'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bill_reference')
                    ->label(__('vendor_bill.bill_reference'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('bill_date')
                    ->label(__('vendor_bill.bill_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('vendor_bill.status'))
                    ->colors([
                        'success' => VendorBillStatus::Posted,
                        'danger' => VendorBillStatus::Cancelled,
                        'warning' => VendorBillStatus::Draft,
                    ])
                    ->searchable(),
                MoneyColumn::make('total_amount')
                    ->label(__('vendor_bill.total_amount'))
                    ->sortable(),
                MoneyColumn::make('total_tax')
                    ->label(__('vendor_bill.total_tax'))
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
            // RelationManagers\VendorBillLinesRelationManager::class,
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

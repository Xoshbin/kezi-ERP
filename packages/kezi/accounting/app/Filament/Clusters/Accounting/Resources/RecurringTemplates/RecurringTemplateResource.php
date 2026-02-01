<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Kezi\Accounting\Enums\Accounting\RecurringFrequency;
use Kezi\Accounting\Enums\Accounting\RecurringStatus;
use Kezi\Accounting\Enums\Accounting\RecurringTargetType;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\FiscalPosition;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\RecurringTemplate;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Foundation\Models\PaymentTerm;
use Kezi\Product\Models\Product;

class RecurringTemplateResource extends Resource
{
    protected static ?string $model = RecurringTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $cluster = AccountingCluster::class;

    protected static ?int $navigationSort = 1000;

    public static function getModelLabel(): string
    {
        return __('accounting::recurring.template');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::recurring.templates');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Filament::getTenant()?->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::recurring.general_information'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('accounting::recurring.template_name'))
                                    ->required()
                                    ->maxLength(255),
                                Select::make('status')
                                    ->options(RecurringStatus::class)
                                    ->required()
                                    ->default(RecurringStatus::Active),
                                Select::make('target_type')
                                    ->label(__('accounting::recurring.target_type'))
                                    ->options(RecurringTargetType::class)
                                    ->required()
                                    ->live()
                                    ->default(RecurringTargetType::JournalEntry),
                            ]),
                    ]),

                Section::make(__('accounting::recurring.scheduling'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Select::make('frequency')
                                    ->options(RecurringFrequency::class)
                                    ->required()
                                    ->default(RecurringFrequency::Monthly),
                                TextInput::make('interval')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1),
                                DatePicker::make('start_date')
                                    ->required()
                                    ->default(now()),
                                DatePicker::make('end_date'),
                            ]),
                    ]),

                Section::make(__('accounting::recurring.template_data'))
                    ->schema([
                        Grid::make(1)
                            ->statePath('template_data')
                            ->schema(function (Get $get): array {
                                $targetType = $get('target_type');
                                if ($targetType instanceof RecurringTargetType) {
                                    $targetType = $targetType->value;
                                }

                                return match ($targetType) {
                                    RecurringTargetType::JournalEntry->value => static::getJournalEntrySchema(),
                                    RecurringTargetType::Invoice->value => static::getInvoiceSchema(),
                                    default => [],
                                };
                            }),
                    ]),
            ]);
    }

    protected static function getJournalEntrySchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    Select::make('journal_id')
                        ->label(__('accounting::journal.journal'))
                        ->options(fn () => Journal::where('company_id', Filament::getTenant()?->id)->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('currency_id')
                        ->label(__('accounting::currency.currency'))
                        ->options(fn () => Currency::pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Textarea::make('description')
                        ->columnSpanFull(),
                ]),
            Repeater::make('lines')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            Select::make('account_id')
                                ->label(__('accounting::account.account'))
                                ->options(fn () => Account::where('company_id', Filament::getTenant()?->id)->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                            TextInput::make('debit')
                                ->numeric()
                                ->default(0),
                            TextInput::make('credit')
                                ->numeric()
                                ->default(0),
                            Select::make('partner_id')
                                ->label(__('accounting::recurring.partner'))
                                ->options(fn () => Partner::where('company_id', Filament::getTenant()?->id)->pluck('name', 'id'))
                                ->searchable(),
                        ]),
                ])
                ->columns(1)
                ->required(),
        ];
    }

    protected static function getInvoiceSchema(): array
    {
        return [
            Grid::make(3)
                ->schema([
                    Select::make('customer_id')
                        ->label(__('accounting::recurring.customer'))
                        ->options(fn () => Partner::where('company_id', Filament::getTenant()?->id)->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('currency_id')
                        ->label(__('accounting::currency.currency'))
                        ->options(fn () => Currency::pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('payment_term_id')
                        ->label(__('accounting::recurring.payment_term'))
                        ->options(fn () => PaymentTerm::where('company_id', Filament::getTenant()?->id)->pluck('name', 'id'))
                        ->searchable(),
                    Select::make('fiscal_position_id')
                        ->label(__('accounting::recurring.fiscal_position'))
                        ->options(fn () => FiscalPosition::where('company_id', Filament::getTenant()?->id)->pluck('name', 'id'))
                        ->searchable(),
                    Textarea::make('description')
                        ->columnSpanFull(),
                ]),
            Repeater::make('lines')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('description')->required(),
                            TextInput::make('quantity')->numeric()->required()->default(1),
                            TextInput::make('unit_price')->numeric()->required(),
                            Select::make('income_account_id')
                                ->label(__('accounting::account.income_account'))
                                ->options(fn () => Account::where('company_id', Filament::getTenant()?->id)->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                            Select::make('product_id')
                                ->label(__('accounting::recurring.product'))
                                ->options(fn () => Product::pluck('name', 'id'))
                                ->searchable(),
                            Select::make('tax_id')
                                ->label(__('accounting::recurring.tax'))
                                ->options(fn () => Tax::where('company_id', Filament::getTenant()?->id)->pluck('name', 'id'))
                                ->searchable(),
                        ]),
                ])
                ->columns(1)
                ->required(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('frequency')
                    ->badge(),
                TextColumn::make('next_run_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('target_type')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringTemplates::route('/'),
            'create' => Pages\CreateRecurringTemplate::route('/create'),
            'edit' => Pages\EditRecurringTemplate::route('/{record}/edit'),
        ];
    }
}

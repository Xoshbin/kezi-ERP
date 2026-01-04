<?php

namespace Modules\Accounting\Filament\Resources;

use App\Models\Company;
use BackedEnum;
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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Accounting\Enums\Accounting\RecurringFrequency;
use Modules\Accounting\Enums\Accounting\RecurringStatus;
use Modules\Accounting\Enums\Accounting\RecurringTargetType;
use Modules\Accounting\Filament\Resources\RecurringTemplateResource\Pages;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\RecurringTemplate;
use Modules\Foundation\Filament\Forms\Components\MoneyInput;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class RecurringTemplateResource extends Resource
{
    protected static ?string $model = RecurringTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    public static function getNavigationGroup(): ?string
    {
        return 'Accounting';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Schedule')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Select::make('status')
                            ->options(RecurringStatus::class)
                            ->required()
                            ->default(RecurringStatus::Active),
                        Select::make('frequency')
                            ->options(RecurringFrequency::class)
                            ->required()
                            ->default(RecurringFrequency::Monthly)
                            ->live(),
                        TextInput::make('interval')
                            ->integer()
                            ->required()
                            ->default(1)
                            ->minValue(1),
                        DatePicker::make('start_date')
                            ->required()
                            ->default(now()),
                        DatePicker::make('next_run_date')
                            ->required()
                            ->default(now()->addMonth()),
                        DatePicker::make('end_date'),
                    ])->columns(3),

                Section::make('Configuration')
                    ->schema([
                        Select::make('target_type')
                            ->options(RecurringTargetType::class)
                            ->required()
                            ->live()
                            ->default(RecurringTargetType::JournalEntry)
                            ->afterStateUpdated(fn (Set $set) => $set('template_data', [])),

                        Grid::make()
                            ->schema(fn (Get $get) => match ($get('target_type')) {
                                RecurringTargetType::JournalEntry->value => self::getJournalEntrySchema(),
                                RecurringTargetType::Invoice->value => self::getInvoiceSchema(),
                                default => [],
                            })
                            ->statePath('template_data'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('next_run_date')->date()->sortable(),
                TextColumn::make('frequency')->badge(),
                TextColumn::make('target_type')->badge(),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringTemplates::route('/'),
            'create' => Pages\CreateRecurringTemplate::route('/create'),
            'edit' => Pages\EditRecurringTemplate::route('/{record}/edit'),
        ];
    }

    protected static function getJournalEntrySchema(): array
    {
        return [
            TranslatableSelect::forModel('journal_id', Journal::class, 'name')
                ->label('Journal')
                ->required()
                ->columnSpan(1),
            TranslatableSelect::forModel('currency_id', Currency::class, 'name')
                ->label('Currency')
                ->required()
                ->default(fn () => (Filament::getTenant() instanceof Company) ? Filament::getTenant()->currency_id : null)
                ->columnSpan(1),
            TextInput::make('reference'),
            Textarea::make('description')->columnSpanFull(),

            Repeater::make('lines')
                ->schema([
                    TranslatableSelect::forModel('account_id', Account::class)
                        ->label('Account')
                        ->required()
                        ->searchable()
                        ->columnSpan(3),
                    MoneyInput::make('debit')
                        ->currencyField('../../currency_id')
                        ->default(0)
                        ->columnSpan(2),
                    MoneyInput::make('credit')
                        ->currencyField('../../currency_id')
                        ->default(0)
                        ->columnSpan(2),
                    TranslatableSelect::forModel('partner_id', Partner::class, 'name')
                        ->label('Partner')
                        ->searchable()
                        ->columnSpan(3),
                    TextInput::make('description')->columnSpan(2),
                ])
                ->columns(12)
                ->columnSpanFull()
                ->minItems(2),
        ];
    }

    protected static function getInvoiceSchema(): array
    {
        return [
            TranslatableSelect::forModel('customer_id', Partner::class, 'name')
                ->label('Customer')
                ->required(),
            TranslatableSelect::forModel('currency_id', Currency::class, 'name')
                ->label('Currency')
                ->required()
                ->default(fn () => (Filament::getTenant() instanceof Company) ? Filament::getTenant()->currency_id : null),
            // Add Payment Terms etc
            Repeater::make('lines')
                ->label('Invoice Lines')
                ->schema([
                    TextInput::make('description')->required(),
                    TextInput::make('quantity')->numeric()->default(1)->required(),
                    MoneyInput::make('unit_price')
                        ->currencyField('../../currency_id')
                        ->required(),
                    TranslatableSelect::forModel('income_account_id', Account::class)
                        ->label('Income Account')
                        ->required(),
                ])
                ->columns(4)
                ->columnSpanFull(),
        ];
    }
}

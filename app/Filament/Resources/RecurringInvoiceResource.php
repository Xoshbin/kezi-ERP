<?php

namespace App\Filament\Resources;

use App\DataTransferObjects\Sales\CreateRecurringInvoiceTemplateDTO;
use App\DataTransferObjects\Sales\RecurringInvoiceLineDTO;
use App\Enums\RecurringInvoice\RecurringFrequency;
use App\Enums\RecurringInvoice\RecurringStatus;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\RecurringInvoiceResource\Pages;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\Account;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Product;
use App\Models\RecurringInvoiceTemplate;
use App\Models\Tax;
use App\Services\RecurringInterCompanyService;
use Brick\Money\Money;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecurringInvoiceResource extends Resource
{
    protected static ?string $model = RecurringInvoiceTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return 'Sales';
    }

    public static function getNavigationLabel(): string
    {
        return 'Recurring Invoices';
    }

    public static function getModelLabel(): string
    {
        return 'Recurring Invoice Template';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Recurring Invoice Templates';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Template Information')
                ->description('Basic information about the recurring invoice template')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Template Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Monthly Management Fee'),
                        Forms\Components\Select::make('target_company_id')
                            ->label('Target Company')
                            ->options(function () {
                                $currentCompanyId = Auth::user()->company_id;
                                return Partner::where('company_id', $currentCompanyId)
                                    ->whereNotNull('linked_company_id')
                                    ->with('linkedCompany')
                                    ->get()
                                    ->pluck('linkedCompany.name', 'linked_company_id');
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('Select target company'),
                    ]),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->maxLength(1000)
                        ->placeholder('Optional description of this recurring charge'),
                ]),

            Section::make('Scheduling Configuration')
                ->description('Configure when and how often invoices are generated')
                ->icon('heroicon-o-calendar')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('frequency')
                            ->label('Frequency')
                            ->options(RecurringFrequency::options())
                            ->required()
                            ->live()
                            ->default(RecurringFrequency::Monthly),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->placeholder('Optional - leave blank for indefinite'),
                    ]),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('day_of_month')
                            ->label('Day of Month')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(28)
                            ->default(1)
                            ->helperText('Day of the month to generate invoices (1-28)'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(RecurringStatus::options())
                            ->default(RecurringStatus::Active)
                            ->required(),
                    ]),
                ]),

            Section::make('Financial Configuration')
                ->description('Configure accounts and tax settings')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('income_account_id')
                            ->label('Income Account')
                            ->options(function () {
                                return Account::where('company_id', Auth::user()->company_id)
                                    ->where('type', 'income')
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('expense_account_id')
                            ->label('Expense Account')
                            ->options(function () {
                                return Account::where('type', 'expense')
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable(),
                    ]),
                    Forms\Components\Select::make('tax_id')
                        ->label('Default Tax')
                        ->options(function () {
                            return Tax::where('company_id', Auth::user()->company_id)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->placeholder('No tax'),
                ]),

            Section::make('Line Items')
                ->description('Configure the items to be included in each invoice')
                ->icon('heroicon-o-list-bullet')
                ->schema([
                    Repeater::make('lines')
                        ->label('Invoice Lines')
                        ->schema([
                            Forms\Components\Grid::make(4)->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->options(function () {
                                        return Product::where('company_id', Auth::user()->company_id)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->placeholder('Optional'),
                                Forms\Components\TextInput::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->placeholder('Service description'),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                MoneyInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->required(),
                            ]),
                            Forms\Components\Select::make('tax_id')
                                ->label('Tax')
                                ->options(function () {
                                    return Tax::where('company_id', Auth::user()->company_id)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->placeholder('No tax'),
                        ])
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('Add Line Item')
                        ->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Template Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('targetCompany.name')
                    ->label('Target Company')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('frequency')
                    ->label('Frequency')
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->badge()
                    ->color(fn ($state): string => $state->color()),
                Tables\Columns\TextColumn::make('next_run_date')
                    ->label('Next Run')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('generation_count')
                    ->label('Generated')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_generated_at')
                    ->label('Last Generated')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(RecurringStatus::options()),
                Tables\Filters\SelectFilter::make('frequency')
                    ->options(RecurringFrequency::options()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate')
                    ->label('Generate Now')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (RecurringInvoiceTemplate $record): bool => $record->status === RecurringStatus::Active)
                    ->action(function (RecurringInvoiceTemplate $record) {
                        // This would trigger manual generation
                        // Implementation would go here
                    }),
                Tables\Actions\Action::make('pause')
                    ->label('Pause')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn (RecurringInvoiceTemplate $record): bool => $record->status === RecurringStatus::Active)
                    ->action(function (RecurringInvoiceTemplate $record) {
                        app(RecurringInterCompanyService::class)->pauseTemplate($record);
                    }),
                Tables\Actions\Action::make('resume')
                    ->label('Resume')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (RecurringInvoiceTemplate $record): bool => $record->status === RecurringStatus::Paused)
                    ->action(function (RecurringInvoiceTemplate $record) {
                        app(RecurringInterCompanyService::class)->resumeTemplate($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Auth::user()->company_id)
            ->with(['company', 'targetCompany', 'currency']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringInvoices::route('/'),
            'create' => Pages\CreateRecurringInvoice::route('/create'),
            'view' => Pages\ViewRecurringInvoice::route('/{record}'),
            'edit' => Pages\EditRecurringInvoice::route('/{record}/edit'),
        ];
    }
}

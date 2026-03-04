<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash;

use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource\Pages;
use Kezi\Accounting\Filament\Forms\Components\AccountSelectField;
use Kezi\Foundation\Filament\Forms\Components\PartnerSelectField;
use Kezi\Payment\Enums\PettyCash\PettyCashVoucherStatus;
use Kezi\Payment\Models\PettyCash\PettyCashVoucher;
use Kezi\Payment\Services\PettyCash\PettyCashService;

class PettyCashVoucherResource extends Resource
{
    protected static ?string $model = PettyCashVoucher::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.banking_cash');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::petty_cash.voucher.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::petty_cash.voucher.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::petty_cash.voucher.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::petty_cash.voucher.expense_details'))
                    ->schema([
                        Select::make('fund_id')
                            ->relationship('fund', 'name', fn ($query) => $query->where('status', 'active'))
                            ->required()
                            ->label(__('accounting::petty_cash.fields.petty_cash_fund'))
                            ->searchable()
                            ->preload(),

                        DatePicker::make('voucher_date')
                            ->required()
                            ->default(now())
                            ->label(__('accounting::petty_cash.fields.expense_date')),

                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('IQD')
                            ->label(__('accounting::petty_cash.fields.amount')),

                        AccountSelectField::make('expense_account_id')
                            ->accountFilter('expense')
                            ->required()
                            ->label(__('accounting::petty_cash.fields.expense_category'))
                            ->helperText(__('accounting::petty_cash.helpers.expense_category')),

                        PartnerSelectField::make('partner_id')
                            ->label(__('accounting::petty_cash.fields.vendor_payee')),

                        Textarea::make('description')
                            ->required()
                            ->rows(3)
                            ->rows(3) // Removed duplicate
                            ->label(__('accounting::petty_cash.fields.description'))
                            ->helperText(__('accounting::petty_cash.helpers.expense_description'))
                            ->columnSpanFull(),

                        TextInput::make('receipt_reference')
                            ->label(__('accounting::petty_cash.fields.receipt_reference'))
                            ->helperText(__('accounting::petty_cash.helpers.receipt_reference')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('voucher_number')
                    ->label(__('accounting::petty_cash.voucher.voucher_number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('voucher_date')
                    ->label(__('accounting::petty_cash.fields.expense_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fund.name')
                    ->label(__('accounting::petty_cash.fields.petty_cash_fund'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('accounting::petty_cash.fields.amount'))
                    ->money(fn (PettyCashVoucher $record) => $record->fund->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('expenseAccount.name')
                    ->label(__('accounting::petty_cash.fields.expense_category'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('accounting::petty_cash.fields.description'))
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PettyCashVoucherStatus::class),
                Tables\Filters\SelectFilter::make('fund_id')
                    ->relationship('fund', 'name'),
            ])
            ->actions([
                Actions\Action::make('post')
                    ->label(__('accounting::petty_cash.actions.post'))
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->requiresConfirmation()
                    ->modalHeading(__('accounting::petty_cash.voucher.post_modal_heading'))
                    ->modalDescription(__('accounting::petty_cash.voucher.post_modal_description'))
                    ->visible(fn (PettyCashVoucher $record) => $record->status === PettyCashVoucherStatus::Draft)
                    ->action(function (PettyCashVoucher $record) {
                        app(PettyCashService::class)->postVoucher($record, auth()->user());
                    }),

                Actions\EditAction::make()
                    ->visible(fn (PettyCashVoucher $record) => $record->status === PettyCashVoucherStatus::Draft),
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
            'index' => Pages\ListPettyCashVouchers::route('/'),
            'create' => Pages\CreatePettyCashVoucher::route('/create'),
            'edit' => Pages\EditPettyCashVoucher::route('/{record}/edit'),
        ];
    }
}

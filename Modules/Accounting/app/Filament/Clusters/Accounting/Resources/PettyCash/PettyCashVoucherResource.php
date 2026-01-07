<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash;

use Brick\Money\Money;
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
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource\Pages;
use Modules\Payment\Enums\PettyCash\PettyCashVoucherStatus;
use Modules\Payment\Models\PettyCash\PettyCashVoucher;
use Modules\Payment\Services\PettyCash\PettyCashService;

class PettyCashVoucherResource extends Resource
{
    protected static ?string $model = PettyCashVoucher::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Petty Cash';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expense Details')
                    ->schema([
                        Select::make('fund_id')
                            ->relationship('fund', 'name', fn ($query) => $query->where('status', 'active'))
                            ->required()
                            ->label('Petty Cash Fund')
                            ->searchable()
                            ->preload(),

                        DatePicker::make('voucher_date')
                            ->required()
                            ->default(now())
                            ->label('Expense Date'),

                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('IQD')
                            ->label('Amount'),

                        Select::make('expense_account_id')
                            ->relationship('expenseAccount', 'name', fn ($query) => $query->where('type', 'expense'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Expense Category')
                            ->helperText('Select the type of expense'),

                        Select::make('partner_id')
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Vendor/Payee (Optional)'),

                        Textarea::make('description')
                            ->required()
                            ->rows(3)
                            ->label('Description')
                            ->helperText('Describe the purpose of this expense')
                            ->columnSpanFull(),

                        TextInput::make('receipt_reference')
                            ->label('Receipt Reference')
                            ->helperText('External receipt number'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('voucher_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('voucher_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fund.name')
                    ->label('Fund')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money(fn (PettyCashVoucher $record) => $record->fund->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('expenseAccount.name')
                    ->label('Category')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
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
                    ->label('Post')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Post Petty Cash Voucher')
                    ->modalDescription('This will create a journal entry and update the fund balance.')
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

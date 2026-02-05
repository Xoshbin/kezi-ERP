<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Tables;

use \Filament\Actions\BulkActionGroup;
use \Filament\Actions\DeleteBulkAction;
use \Filament\Actions\EditAction;
use \Filament\Actions\ViewAction;
use Filament\Tables\Table;

class CashAdvancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('advance_number')
                    ->label(__('hr::cash_advance.advance_number'))
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('employee.first_name') // Accessor full_name might not work for search/sort easily without configuration
                    ->label(__('hr::cash_advance.employee'))
                    ->formatStateUsing(fn ($record) => $record->employee->full_name)
                    ->searchable(['first_name', 'last_name']),
                \Filament\Tables\Columns\TextColumn::make('requested_amount')
                    ->label(__('hr::cash_advance.requested_amount'))
                    ->money(fn ($record) => $record->currency->code)
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label(__('hr::cash_advance.fields.status'))
                    ->badge(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label(__('hr::cash_advance.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options(\Kezi\HR\Enums\CashAdvanceStatus::class),
                \Filament\Tables\Filters\SelectFilter::make('employee')
                    ->relationship('employee', 'first_name') // simplified
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (\Kezi\HR\Models\CashAdvance $record) => $record->status === \Kezi\HR\Enums\CashAdvanceStatus::Draft),
                \Filament\Actions\Action::make('submit')
                    ->label(__('hr::cash_advance.submit'))
                    ->icon('heroicon-m-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (\Kezi\HR\Models\CashAdvance $record) => $record->status === \Kezi\HR\Enums\CashAdvanceStatus::Draft)
                    ->action(function (\Kezi\HR\Models\CashAdvance $record) {
                        app(\Kezi\HR\Services\HumanResources\CashAdvanceService::class)->submitForApproval($record, auth()->user());
                    }),
                \Filament\Actions\Action::make('approve')
                    ->label(__('hr::cash_advance.approve'))
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (\Kezi\HR\Models\CashAdvance $record) => $record->status === \Kezi\HR\Enums\CashAdvanceStatus::PendingApproval)
                    ->form([
                        \Filament\Forms\Components\TextInput::make('approved_amount')
                            ->label(__('hr::cash_advance.approved_amount'))
                            ->required()
                            ->numeric()
                            ->default(fn (\Kezi\HR\Models\CashAdvance $record) => (string) $record->requested_amount->getAmount()),
                    ])
                    ->action(function (\Kezi\HR\Models\CashAdvance $record, array $data) {
                        $amount = \Brick\Money\Money::of($data['approved_amount'], $record->currency->code);
                        app(\Kezi\HR\Services\HumanResources\CashAdvanceService::class)->approve($record, $amount, auth()->user());
                    }),
                \Filament\Actions\Action::make('reject')
                    ->label(__('hr::cash_advance.reject'))
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (\Kezi\HR\Models\CashAdvance $record) => $record->status === \Kezi\HR\Enums\CashAdvanceStatus::PendingApproval)
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label(__('hr::cash_advance.rejection_reason'))
                            ->required(),
                    ])
                    ->action(function (\Kezi\HR\Models\CashAdvance $record, array $data) {
                        app(\Kezi\HR\Services\HumanResources\CashAdvanceService::class)->reject($record, $data['reason'], auth()->user());
                    }),
                \Filament\Actions\Action::make('disburse')
                    ->label(__('hr::cash_advance.disburse'))
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (\Kezi\HR\Models\CashAdvance $record) => $record->status === \Kezi\HR\Enums\CashAdvanceStatus::Approved)
                    ->form([
                        \Filament\Forms\Components\Select::make('bank_account_id')
                            ->label(__('hr::cash_advance.bank_account'))
                            ->options(\Kezi\Accounting\Models\Account::where('type', \Kezi\Accounting\Enums\Accounting\AccountType::BankAndCash)->get()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (\Kezi\HR\Models\CashAdvance $record, array $data) {
                        app(\Kezi\HR\Services\HumanResources\CashAdvanceService::class)->disburse($record, (int) $data['bank_account_id'], auth()->user());
                    }),
                \Filament\Actions\Action::make('settle')
                    ->label(__('hr::cash_advance.settle'))
                    ->icon('heroicon-m-scale')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (\Kezi\HR\Models\CashAdvance $record) => $record->status === \Kezi\HR\Enums\CashAdvanceStatus::PendingSettlement)
                    ->form([
                        \Filament\Forms\Components\Select::make('settlement_method')
                            ->label(__('hr::cash_advance.settlement_method'))
                            ->options(__('hr::cash_advance.settlement_methods'))
                            ->required(),
                        \Filament\Forms\Components\Select::make('bank_account_id')
                            ->label(__('hr::cash_advance.bank_account'))
                            ->helperText(__('hr::cash_advance.bank_account_helper'))
                            ->options(\Kezi\Accounting\Models\Account::where('type', \Kezi\Accounting\Enums\Accounting\AccountType::BankAndCash)->get()->pluck('name', 'id'))
                            ->visible(fn ($get) => in_array($get('settlement_method'), ['cash_return', 'reimbursement'])),
                    ])
                    ->action(function (\Kezi\HR\Models\CashAdvance $record, array $data) {
                        app(\Kezi\HR\Services\HumanResources\CashAdvanceService::class)->settle($record, $data['settlement_method'], $data['bank_account_id'] ?? null, auth()->user());
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

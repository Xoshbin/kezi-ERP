<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class CashAdvancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('advance_number')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('employee.first_name') // Accessor full_name might not work for search/sort easily without configuration
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->employee->full_name)
                    ->searchable(['first_name', 'last_name']),
                \Filament\Tables\Columns\TextColumn::make('requested_amount')
                    ->money(fn ($record) => $record->currency->code)
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options(\Modules\HR\Enums\CashAdvanceStatus::class),
                \Filament\Tables\Filters\SelectFilter::make('employee')
                    ->relationship('employee', 'first_name') // simplified
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (\Modules\HR\Models\CashAdvance $record) => $record->status === \Modules\HR\Enums\CashAdvanceStatus::Draft),
                \Filament\Actions\Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (\Modules\HR\Models\CashAdvance $record) => $record->status === \Modules\HR\Enums\CashAdvanceStatus::Draft)
                    ->action(function (\Modules\HR\Models\CashAdvance $record) {
                        app(\Modules\HR\Services\HumanResources\CashAdvanceService::class)->submitForApproval($record, auth()->user());
                    }),
                \Filament\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (\Modules\HR\Models\CashAdvance $record) => $record->status === \Modules\HR\Enums\CashAdvanceStatus::PendingApproval)
                    ->form([
                        \Filament\Forms\Components\TextInput::make('approved_amount')
                            ->label('Approved Amount')
                            ->required()
                            ->numeric()
                            ->default(fn (\Modules\HR\Models\CashAdvance $record) => (string) $record->requested_amount->getAmount()),
                    ])
                    ->action(function (\Modules\HR\Models\CashAdvance $record, array $data) {
                        $amount = \Brick\Money\Money::of($data['approved_amount'], $record->currency->code);
                        app(\Modules\HR\Services\HumanResources\CashAdvanceService::class)->approve($record, $amount, auth()->user());
                    }),
                \Filament\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (\Modules\HR\Models\CashAdvance $record) => $record->status === \Modules\HR\Enums\CashAdvanceStatus::PendingApproval)
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(function (\Modules\HR\Models\CashAdvance $record, array $data) {
                        app(\Modules\HR\Services\HumanResources\CashAdvanceService::class)->reject($record, $data['reason'], auth()->user());
                    }),
                \Filament\Actions\Action::make('disburse')
                    ->label('Disburse')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (\Modules\HR\Models\CashAdvance $record) => $record->status === \Modules\HR\Enums\CashAdvanceStatus::Approved)
                    ->form([
                        \Filament\Forms\Components\Select::make('bank_account_id')
                            ->label('Bank Account')
                            ->options(\Modules\Accounting\Models\Account::where('type', \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash)->get()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (\Modules\HR\Models\CashAdvance $record, array $data) {
                        app(\Modules\HR\Services\HumanResources\CashAdvanceService::class)->disburse($record, (int) $data['bank_account_id'], auth()->user());
                    }),
                \Filament\Actions\Action::make('settle')
                    ->label('Settle')
                    ->icon('heroicon-m-scale')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (\Modules\HR\Models\CashAdvance $record) => $record->status === \Modules\HR\Enums\CashAdvanceStatus::PendingSettlement)
                    ->form([
                        \Filament\Forms\Components\Select::make('settlement_method')
                            ->label('Settlement Method')
                            ->options([
                                'none' => 'None (Exact Match/Carry Forward)',
                                'cash_return' => 'Cash Return (Employee pays back)',
                                'reimbursement' => 'Reimbursement (Company pays employee)',
                            ])
                            ->required(),
                        \Filament\Forms\Components\Select::make('bank_account_id')
                            ->label('Bank Account')
                            ->helperText('Required for Cash Return or Reimbursement')
                            ->options(\Modules\Accounting\Models\Account::where('type', \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash)->get()->pluck('name', 'id'))
                            ->visible(fn (\Filament\Forms\Get $get) => in_array($get('settlement_method'), ['cash_return', 'reimbursement'])),
                    ])
                    ->action(function (\Modules\HR\Models\CashAdvance $record, array $data) {
                        app(\Modules\HR\Services\HumanResources\CashAdvanceService::class)->settle($record, $data['settlement_method'], $data['bank_account_id'] ?? null, auth()->user());
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

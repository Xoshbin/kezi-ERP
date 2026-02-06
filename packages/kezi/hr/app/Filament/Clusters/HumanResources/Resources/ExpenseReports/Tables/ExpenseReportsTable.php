<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;

class ExpenseReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('report_number')
                    ->label(__('hr::expense_report.fields.report_number'))
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('cashAdvance.advance_number')
                    ->label(__('hr::expense_report.fields.cash_advance'))
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('hr::expense_report.fields.total_amount'))
                    ->money(fn ($record) => $record->cashAdvance?->currency?->code ?? 'USD') // currency might be on cashAdvance
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label(__('hr::expense_report.fields.status'))
                    ->badge(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label(__('hr::expense_report.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options(\Kezi\HR\Enums\ExpenseReportStatus::class),
                \Filament\Tables\Filters\SelectFilter::make('cash_advance_id')
                    ->relationship('cashAdvance', 'advance_number'),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === \Kezi\HR\Enums\ExpenseReportStatus::Draft),
                \Filament\Actions\Action::make('submit')
                    ->label(__('hr::expense_report.actions.submit'))
                    ->icon('heroicon-m-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === \Kezi\HR\Enums\ExpenseReportStatus::Draft)
                    ->action(function ($record) {
                        app(\Kezi\HR\Services\HumanResources\CashAdvanceService::class)->submitExpenseReport($record, auth()->user());
                    }),
                \Filament\Actions\Action::make('approve')
                    ->label(__('hr::expense_report.actions.approve'))
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === \Kezi\HR\Enums\ExpenseReportStatus::Submitted)
                    ->action(function ($record) {
                        app(\Kezi\HR\Services\HumanResources\CashAdvanceService::class)->approveExpenseReport($record, auth()->user());
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

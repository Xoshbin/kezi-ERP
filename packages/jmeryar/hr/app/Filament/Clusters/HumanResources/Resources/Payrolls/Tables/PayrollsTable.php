<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Tables;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Jmeryar\Foundation\Filament\Tables\Columns\MoneyColumn;
use Jmeryar\HR\Models\Payroll;
use Jmeryar\HR\Services\HumanResources\PayrollService;

class PayrollsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payroll_number')
                    ->label(__('hr::payroll.fields.payroll_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.full_name')
                    ->label(__('hr::payroll.fields.employee'))
                    ->searchable(['employees.first_name', 'employees.last_name'])
                    ->sortable(),

                TextColumn::make('period_start_date')
                    ->label(__('hr::payroll.fields.period_start_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('period_end_date')
                    ->label(__('hr::payroll.fields.period_end_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('pay_date')
                    ->label(__('hr::payroll.fields.pay_date'))
                    ->date()
                    ->sortable(),

                MoneyColumn::make('gross_salary')
                    ->label(__('hr::payroll.fields.gross_salary'))
                    ->sortable(),

                MoneyColumn::make('total_deductions')
                    ->label(__('hr::payroll.fields.total_deductions'))
                    ->sortable(),

                MoneyColumn::make('net_salary')
                    ->label(__('hr::payroll.fields.net_salary'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('hr::payroll.fields.status'))
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'processed',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => __("hr::payroll.status.{$state}"))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('hr::payroll.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Payroll $record): bool => $record->status === 'draft'),

                Action::make('approve')
                    ->label(__('hr::payroll.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Green)
                    ->visible(fn (Payroll $record): bool => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading(__('hr::payroll.actions.approve_payroll'))
                    ->modalDescription(__('hr::payroll.actions.approve_payroll_description'))
                    ->action(function (Payroll $record) {
                        $user = auth()->user();
                        if (! $user) {
                            throw new Exception('User must be authenticated to approve payroll');
                        }
                        $payrollService = app(PayrollService::class);
                        $payrollService->approvePayroll($record, $user);

                        return redirect()->back();
                    })
                    ->successNotificationTitle(__('hr::payroll.notifications.approved')),

                Action::make('pay')
                    ->label(__('hr::payroll.actions.pay'))
                    ->icon('heroicon-o-currency-dollar')
                    ->color(Color::Blue)
                    ->visible(fn (Payroll $record): bool => $record->status === 'processed' && ! $record->payment_id)
                    ->requiresConfirmation()
                    ->modalHeading(__('hr::payroll.actions.pay_employee'))
                    ->modalDescription(
                        fn (Payroll $record): string => __('hr::payroll.actions.pay_employee_description', [
                            'employee' => $record->employee->full_name,
                            'amount' => $record->net_salary->formatTo('en_US'),
                        ])
                    )
                    ->action(function (Payroll $record) {
                        $user = auth()->user();
                        if (! $user) {
                            throw new Exception('User must be authenticated to pay employee');
                        }
                        $payrollService = app(PayrollService::class);
                        $payrollService->payEmployee($record, $user);

                        return redirect()->back();
                    })
                    ->successNotificationTitle(__('hr::payroll.notifications.paid')),

                Action::make('view_payment')
                    ->label(__('hr::payroll.actions.view_payment'))
                    ->icon('heroicon-o-eye')
                    ->color(Color::Gray)
                    ->visible(fn (Payroll $record): bool => $record->payment_id !== null)
                    ->url(fn (Payroll $record): string => route('filament.jmeryar.accounting.resources.payments.edit', ['record' => $record->payment_id, 'tenant' => $record->company_id]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function (Payroll $record) {
                                if ($record->status === 'draft') {
                                    $record->delete();
                                }
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

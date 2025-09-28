<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Tables;

use Exception;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Modules\HR\Models\Payroll;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Colors\Color;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Modules\HR\Services\HumanResources\PayrollService;
use Modules\Foundation\Filament\Tables\Columns\MoneyColumn;

class PayrollsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payroll_number')
                    ->label(__('payroll.fields.payroll_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.full_name')
                    ->label(__('payroll.fields.employee'))
                    ->searchable(['employees.first_name', 'employees.last_name'])
                    ->sortable(),

                TextColumn::make('period_start_date')
                    ->label(__('payroll.fields.period_start_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('period_end_date')
                    ->label(__('payroll.fields.period_end_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('pay_date')
                    ->label(__('payroll.fields.pay_date'))
                    ->date()
                    ->sortable(),

                MoneyColumn::make('gross_salary')
                    ->label(__('payroll.fields.gross_salary'))
                    ->sortable(),

                MoneyColumn::make('total_deductions')
                    ->label(__('payroll.fields.total_deductions'))
                    ->sortable(),

                MoneyColumn::make('net_salary')
                    ->label(__('payroll.fields.net_salary'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('payroll.fields.status'))
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'processed',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn(string $state): string => __("payroll.status.{$state}"))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('payroll.fields.created_at'))
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

                Action::make('approve')
                    ->label(__('payroll.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Green)
                    ->visible(fn(Payroll $record): bool => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading(__('payroll.actions.approve_payroll'))
                    ->modalDescription(__('payroll.actions.approve_payroll_description'))
                    ->action(function (Payroll $record) {
                        $user = auth()->user();
                        if (! $user) {
                            throw new Exception('User must be authenticated to approve payroll');
                        }
                        $payrollService = app(PayrollService::class);
                        $payrollService->approvePayroll($record, $user);

                        return redirect()->back();
                    })
                    ->successNotificationTitle(__('payroll.notifications.approved')),

                Action::make('pay')
                    ->label(__('payroll.actions.pay'))
                    ->icon('heroicon-o-currency-dollar')
                    ->color(Color::Blue)
                    ->visible(fn(Payroll $record): bool => $record->status === 'processed' && ! $record->payment_id)
                    ->requiresConfirmation()
                    ->modalHeading(__('payroll.actions.pay_employee'))
                    ->modalDescription(
                        fn(Payroll $record): string => __('payroll.actions.pay_employee_description', [
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
                    ->successNotificationTitle(__('payroll.notifications.paid')),

                Action::make('view_payment')
                    ->label(__('payroll.actions.view_payment'))
                    ->icon('heroicon-o-eye')
                    ->color(Color::Gray)
                    ->visible(fn(Payroll $record): bool => $record->payment_id !== null)
                    ->url(fn(Payroll $record): string => route('filament.jmeryar.resources.payments.edit', $record->payment_id))
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

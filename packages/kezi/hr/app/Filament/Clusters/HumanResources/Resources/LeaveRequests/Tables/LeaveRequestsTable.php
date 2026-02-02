<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_number')
                    ->label(__('hr::leave_request.request_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.full_name')
                    ->label(__('hr::leave_request.employee'))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('leaveType.name')
                    ->label(__('hr::leave_request.leave_type'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label(__('hr::leave_request.start_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('hr::leave_request.end_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('days_requested')
                    ->label(__('hr::leave_request.days_requested'))
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' '.__('hr::leave_request.days'))
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label(__('hr::leave_request.status'))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'secondary' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => __('hr::leave_request.status_'.$state))
                    ->sortable(),

                TextColumn::make('approvedByUser.name')
                    ->label(__('hr::leave_request.approved_by'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at')
                    ->label(__('hr::leave_request.approved_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('delegateEmployee.full_name')
                    ->label(__('hr::leave_request.delegate_employee'))
                    ->searchable(['first_name', 'last_name'])
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('submitted_at')
                    ->label(__('hr::leave_request.submitted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('hr::leave_request.status'))
                    ->options([
                        'pending' => __('hr::leave_request.status_pending'),
                        'approved' => __('hr::leave_request.status_approved'),
                        'rejected' => __('hr::leave_request.status_rejected'),
                        'cancelled' => __('hr::leave_request.status_cancelled'),
                    ]),

                SelectFilter::make('leave_type_id')
                    ->label(__('hr::leave_request.leave_type'))
                    ->relationship('leaveType', 'name')
                    ->preload(),

                SelectFilter::make('employee_id')
                    ->label(__('hr::leave_request.employee'))
                    ->relationship('employee', 'first_name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

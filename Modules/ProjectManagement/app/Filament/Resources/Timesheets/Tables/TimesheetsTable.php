<?php

namespace Modules\ProjectManagement\Filament\Resources\Timesheets\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\ProjectManagement\Enums\TimesheetStatus;
use Modules\ProjectManagement\Models\Timesheet;
use Modules\ProjectManagement\Services\TimesheetService;

class TimesheetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->employee ? "{$record->employee->first_name} {$record->employee->last_name}" : '')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total_hours')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (Timesheet $record) => $record->status === TimesheetStatus::Draft || $record->status === TimesheetStatus::Rejected),
                    Action::make('submit')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn (Timesheet $record) => $record->status === TimesheetStatus::Draft)
                        ->action(fn (Timesheet $record, TimesheetService $service) => $service->submitTimesheet($record)),
                    Action::make('approve')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Timesheet $record) => $record->status === TimesheetStatus::Submitted)
                        ->action(fn (Timesheet $record, TimesheetService $service) => $service->approveTimesheet($record, auth()->user())),
                    Action::make('reject')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->form([
                            Textarea::make('reason')
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->visible(fn (Timesheet $record) => $record->status === TimesheetStatus::Submitted)
                        ->action(fn (Timesheet $record, array $data, TimesheetService $service) => $service->rejectTimesheet($record, auth()->user(), $data['reason'])),
                    DeleteAction::make()
                        ->visible(fn (Timesheet $record) => $record->status === TimesheetStatus::Draft),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

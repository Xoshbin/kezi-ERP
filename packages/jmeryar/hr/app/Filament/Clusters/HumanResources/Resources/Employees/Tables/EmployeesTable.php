<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_number')
                    ->label(__('hr::employee.employee_number'))
                    ->searchable(),
                TextColumn::make('first_name')
                    ->label(__('hr::employee.first_name'))
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label(__('hr::employee.last_name'))
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label(__('hr::employee.department'))
                    ->searchable(),
                TextColumn::make('position.title')
                    ->label(__('hr::employee.position'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('hr::employee.email'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('hr::employee.phone'))
                    ->searchable(),
                TextColumn::make('date_of_birth')
                    ->label(__('hr::employee.date_of_birth'))
                    ->date()
                    ->sortable(),
                TextColumn::make('gender')
                    ->label(__('hr::employee.gender'))
                    ->searchable(),
                TextColumn::make('marital_status')
                    ->label(__('hr::employee.marital_status'))
                    ->searchable(),
                TextColumn::make('nationality')
                    ->label(__('hr::employee.nationality'))
                    ->searchable(),
                TextColumn::make('national_id')
                    ->label(__('hr::employee.national_id'))
                    ->searchable(),
                TextColumn::make('passport_number')
                    ->label(__('hr::employee.passport_number'))
                    ->searchable(),
                TextColumn::make('address_line_1')
                    ->label(__('hr::employee.address_line_1'))
                    ->searchable(),
                TextColumn::make('address_line_2')
                    ->label(__('hr::employee.address_line_2'))
                    ->searchable(),
                TextColumn::make('city')
                    ->label(__('hr::employee.city'))
                    ->searchable(),
                TextColumn::make('state')
                    ->label(__('hr::employee.state'))
                    ->searchable(),
                TextColumn::make('zip_code')
                    ->label(__('hr::employee.zip_code'))
                    ->searchable(),
                TextColumn::make('country')
                    ->label(__('hr::employee.country'))
                    ->searchable(),
                TextColumn::make('emergency_contact_name')
                    ->label(__('hr::employee.emergency_contact_name'))
                    ->searchable(),
                TextColumn::make('emergency_contact_phone')
                    ->label(__('hr::employee.emergency_contact_phone'))
                    ->searchable(),
                TextColumn::make('emergency_contact_relationship')
                    ->label(__('hr::employee.emergency_contact_relationship'))
                    ->searchable(),
                TextColumn::make('hire_date')
                    ->label(__('hr::employee.hire_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('termination_date')
                    ->label(__('hr::employee.termination_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('employment_status')
                    ->label(__('hr::employee.employment_status'))
                    ->searchable(),
                TextColumn::make('employee_type')
                    ->label(__('hr::employee.employee_type'))
                    ->searchable(),
                TextColumn::make('bank_name')
                    ->label(__('hr::employee.bank_name'))
                    ->searchable(),
                TextColumn::make('bank_account_number')
                    ->label(__('hr::employee.bank_account_number'))
                    ->searchable(),
                TextColumn::make('bank_routing_number')
                    ->label(__('hr::employee.bank_routing_number'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('hr::employee.is_active'))
                    ->boolean(),
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
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->using(function (\Jmeryar\HR\Models\Employee $record, array $data): \Jmeryar\HR\Models\Employee {
                        return app(\Jmeryar\HR\Actions\Employees\UpdateEmployeeAction::class)
                            ->execute($record, \Jmeryar\HR\DataTransferObjects\Employees\EmployeeDTO::fromArray($data));
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

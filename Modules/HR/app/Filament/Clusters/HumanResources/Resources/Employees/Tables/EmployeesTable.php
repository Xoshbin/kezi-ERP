<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
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
                    ->label(__('employee.employee_number'))
                    ->searchable(),
                TextColumn::make('first_name')
                    ->label(__('employee.first_name'))
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label(__('employee.last_name'))
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label(__('employee.department'))
                    ->searchable(),
                TextColumn::make('position.title')
                    ->label(__('employee.position'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('employee.email'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('employee.phone'))
                    ->searchable(),
                TextColumn::make('date_of_birth')
                    ->label(__('employee.date_of_birth'))
                    ->date()
                    ->sortable(),
                TextColumn::make('gender')
                    ->label(__('employee.gender'))
                    ->searchable(),
                TextColumn::make('marital_status')
                    ->label(__('employee.marital_status'))
                    ->searchable(),
                TextColumn::make('nationality')
                    ->label(__('employee.nationality'))
                    ->searchable(),
                TextColumn::make('national_id')
                    ->label(__('employee.national_id'))
                    ->searchable(),
                TextColumn::make('passport_number')
                    ->label(__('employee.passport_number'))
                    ->searchable(),
                TextColumn::make('address_line_1')
                    ->label(__('employee.address_line_1'))
                    ->searchable(),
                TextColumn::make('address_line_2')
                    ->label(__('employee.address_line_2'))
                    ->searchable(),
                TextColumn::make('city')
                    ->label(__('employee.city'))
                    ->searchable(),
                TextColumn::make('state')
                    ->label(__('employee.state'))
                    ->searchable(),
                TextColumn::make('zip_code')
                    ->label(__('employee.zip_code'))
                    ->searchable(),
                TextColumn::make('country')
                    ->label(__('employee.country'))
                    ->searchable(),
                TextColumn::make('emergency_contact_name')
                    ->label(__('employee.emergency_contact_name'))
                    ->searchable(),
                TextColumn::make('emergency_contact_phone')
                    ->label(__('employee.emergency_contact_phone'))
                    ->searchable(),
                TextColumn::make('emergency_contact_relationship')
                    ->label(__('employee.emergency_contact_relationship'))
                    ->searchable(),
                TextColumn::make('hire_date')
                    ->label(__('employee.hire_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('termination_date')
                    ->label(__('employee.termination_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('employment_status')
                    ->label(__('employee.employment_status'))
                    ->searchable(),
                TextColumn::make('employee_type')
                    ->label(__('employee.employee_type'))
                    ->searchable(),
                TextColumn::make('bank_name')
                    ->label(__('employee.bank_name'))
                    ->searchable(),
                TextColumn::make('bank_account_number')
                    ->label(__('employee.bank_account_number'))
                    ->searchable(),
                TextColumn::make('bank_routing_number')
                    ->label(__('employee.bank_routing_number'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('employee.is_active'))
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

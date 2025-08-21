<?php

namespace App\Filament\Clusters\HumanResources\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('department_id')
                    ->relationship('department', 'name'),
                Select::make('position_id')
                    ->relationship('position', 'title'),
                Select::make('manager_id')
                    ->relationship('manager', 'id'),
                TextInput::make('employee_number')
                    ->required(),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                DatePicker::make('date_of_birth'),
                TextInput::make('gender'),
                TextInput::make('marital_status'),
                TextInput::make('nationality'),
                TextInput::make('national_id'),
                TextInput::make('passport_number'),
                TextInput::make('address_line_1'),
                TextInput::make('address_line_2'),
                TextInput::make('city'),
                TextInput::make('state'),
                TextInput::make('zip_code'),
                TextInput::make('country'),
                TextInput::make('emergency_contact_name'),
                TextInput::make('emergency_contact_phone')
                    ->tel(),
                TextInput::make('emergency_contact_relationship'),
                DatePicker::make('hire_date')
                    ->required(),
                DatePicker::make('termination_date'),
                TextInput::make('employment_status')
                    ->required()
                    ->default('active'),
                TextInput::make('employee_type')
                    ->required()
                    ->default('full_time'),
                TextInput::make('bank_name'),
                TextInput::make('bank_account_number'),
                TextInput::make('bank_routing_number'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}

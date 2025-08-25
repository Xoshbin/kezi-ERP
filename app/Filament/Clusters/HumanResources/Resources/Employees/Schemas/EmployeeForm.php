<?php

namespace App\Filament\Clusters\HumanResources\Resources\Employees\Schemas;

use App\Filament\Support\TranslatableSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('employee.basic_information'))
                ->description(__('employee.basic_information_description'))
                ->schema([
                    TextInput::make('employee_number')
                        ->label(__('employee.employee_number'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->columnSpan(1),

                    TextInput::make('first_name')
                        ->label(__('employee.first_name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('last_name')
                        ->label(__('employee.last_name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('email')
                        ->label(__('employee.email'))
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('phone')
                        ->label(__('employee.phone'))
                        ->tel()
                        ->maxLength(20)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('employee.organizational_details'))
                ->description(__('employee.organizational_details_description'))
                ->schema([
                    TranslatableSelect::relationship(
                        'department_id',
                        'department',
                        \App\Models\Department::class,
                        __('employee.department'),
                        'name',
                        null,
                        fn($query) => $query->where('company_id', \Filament\Facades\Filament::getTenant()->id)
                    )
                        ->columnSpan(1),

                    TranslatableSelect::relationship(
                        'position_id',
                        'position',
                        \App\Models\Position::class,
                        __('employee.position'),
                        'title',
                        null,
                        fn($query) => $query->where('company_id', \Filament\Facades\Filament::getTenant()->id)
                    )
                        ->columnSpan(1),

                    TranslatableSelect::standard(
                        'manager_id',
                        \App\Models\Employee::class,
                        ['first_name', 'last_name', 'employee_number'],
                        __('employee.manager'),
                        'first_name',
                        fn($query) => $query->where('company_id', \Filament\Facades\Filament::getTenant()->id)
                    )
                        ->columnSpan(1),

                    TranslatableSelect::standard(
                        'user_id',
                        \App\Models\User::class,
                        ['name', 'email'],
                        __('employee.user_account'),
                        'name',
                        fn($query) => $query->whereHas('companies', fn($q) => $q->where('companies.id', \Filament\Facades\Filament::getTenant()->id))
                    )
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(__('employee.personal_information'))
                ->description(__('employee.personal_information_description'))
                ->schema([
                    DatePicker::make('date_of_birth')
                        ->label(__('employee.date_of_birth'))
                        ->maxDate(now()->subYears(16))
                        ->columnSpan(1),

                    Select::make('gender')
                        ->label(__('employee.gender'))
                        ->options([
                            'male' => __('employee.gender_male'),
                            'female' => __('employee.gender_female'),
                            'other' => __('employee.gender_other'),
                        ])
                        ->columnSpan(1),

                    Select::make('marital_status')
                        ->label(__('employee.marital_status'))
                        ->options([
                            'single' => __('employee.marital_status_single'),
                            'married' => __('employee.marital_status_married'),
                            'divorced' => __('employee.marital_status_divorced'),
                            'widowed' => __('employee.marital_status_widowed'),
                        ])
                        ->columnSpan(1),

                    TextInput::make('nationality')
                        ->label(__('employee.nationality'))
                        ->maxLength(100)
                        ->columnSpan(1),

                    TextInput::make('national_id')
                        ->label(__('employee.national_id'))
                        ->maxLength(50)
                        ->columnSpan(1),

                    TextInput::make('passport_number')
                        ->label(__('employee.passport_number'))
                        ->maxLength(50)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('employee.address_information'))
                ->description(__('employee.address_information_description'))
                ->schema([
                    TextInput::make('address_line_1')
                        ->label(__('employee.address_line_1'))
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('address_line_2')
                        ->label(__('employee.address_line_2'))
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('city')
                        ->label(__('employee.city'))
                        ->maxLength(100)
                        ->columnSpan(1),

                    TextInput::make('state')
                        ->label(__('employee.state'))
                        ->maxLength(100)
                        ->columnSpan(1),

                    TextInput::make('zip_code')
                        ->label(__('employee.zip_code'))
                        ->maxLength(20)
                        ->columnSpan(1),

                    TextInput::make('country')
                        ->label(__('employee.country'))
                        ->maxLength(100)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('employee.emergency_contact'))
                ->description(__('employee.emergency_contact_description'))
                ->schema([
                    TextInput::make('emergency_contact_name')
                        ->label(__('employee.emergency_contact_name'))
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('emergency_contact_phone')
                        ->label(__('employee.emergency_contact_phone'))
                        ->tel()
                        ->maxLength(20)
                        ->columnSpan(1),

                    TextInput::make('emergency_contact_relationship')
                        ->label(__('employee.emergency_contact_relationship'))
                        ->maxLength(100)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('employee.employment_details'))
                ->description(__('employee.employment_details_description'))
                ->schema([
                    DatePicker::make('hire_date')
                        ->label(__('employee.hire_date'))
                        ->required()
                        ->maxDate(now())
                        ->columnSpan(1),

                    DatePicker::make('termination_date')
                        ->label(__('employee.termination_date'))
                        ->minDate(fn ($get) => $get('hire_date'))
                        ->columnSpan(1),

                    Select::make('employment_status')
                        ->label(__('employee.employment_status'))
                        ->options([
                            'active' => __('employee.employment_status_active'),
                            'inactive' => __('employee.employment_status_inactive'),
                            'terminated' => __('employee.employment_status_terminated'),
                            'on_leave' => __('employee.employment_status_on_leave'),
                        ])
                        ->required()
                        ->default('active')
                        ->columnSpan(1),

                    Select::make('employee_type')
                        ->label(__('employee.employee_type'))
                        ->options([
                            'full_time' => __('employee.employee_type_full_time'),
                            'part_time' => __('employee.employee_type_part_time'),
                            'contract' => __('employee.employee_type_contract'),
                            'intern' => __('employee.employee_type_intern'),
                        ])
                        ->required()
                        ->default('full_time')
                        ->columnSpan(1),

                    Toggle::make('is_active')
                        ->label(__('employee.is_active'))
                        ->default(true)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('employee.banking_information'))
                ->description(__('employee.banking_information_description'))
                ->schema([
                    TextInput::make('bank_name')
                        ->label(__('employee.bank_name'))
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('bank_account_number')
                        ->label(__('employee.bank_account_number'))
                        ->maxLength(50)
                        ->columnSpan(1),

                    TextInput::make('bank_routing_number')
                        ->label(__('employee.bank_routing_number'))
                        ->maxLength(50)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }
}

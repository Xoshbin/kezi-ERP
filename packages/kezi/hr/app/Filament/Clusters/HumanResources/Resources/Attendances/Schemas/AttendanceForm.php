<?php

declare(strict_types=1);

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Attendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            \Kezi\HR\Filament\Forms\Components\EmployeeSelectField::make('employee_id')
                ->label(__('hr::attendance.employee'))
                ->required(),
            DatePicker::make('attendance_date')
                ->label(__('hr::attendance.attendance_date'))
                ->required(),
            DateTimePicker::make('clock_in_time')
                ->label(__('hr::attendance.clock_in_time')),
            DateTimePicker::make('clock_out_time')
                ->label(__('hr::attendance.clock_out_time')),
            TextInput::make('total_hours')
                ->label(__('hr::attendance.total_hours'))
                ->numeric(),
            TextInput::make('status')
                ->label(__('hr::attendance.status'))
                ->required()
                ->default('present'),
        ]);
    }
}

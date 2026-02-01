<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\HR\Actions\HumanResources\CreateAttendanceAction;
use Jmeryar\HR\DataTransferObjects\HumanResources\CreateAttendanceDTO;
use Jmeryar\HR\Models\Attendance;
use Jmeryar\HR\Models\Employee;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('CreateAttendanceAction', function () {
    it('creates attendance record and calculates hours correctly', function () {
        // Arrange
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $date = '2023-10-25';

        $dto = new CreateAttendanceDTO(
            company_id: $this->company->id,
            employee_id: $employee->id,
            attendance_date: $date,
            clock_in_time: '09:00:00',
            clock_out_time: '18:00:00', // 9 hours total
            break_start_time: '13:00:00',
            break_end_time: '14:00:00', // 1 hour break
            status: 'present',
            attendance_type: 'regular',
            clock_in_location: 'Office',
            clock_out_location: 'Office',
            clock_in_device: 'Biometric',
            clock_out_device: 'Biometric',
            clock_in_ip: '192.168.1.1',
            clock_out_ip: '192.168.1.1',
            notes: 'Regular day',
            is_manual_entry: false,
            leave_request_id: null,
            created_by_user_id: $this->user->id,
        );

        // Act
        $action = app(CreateAttendanceAction::class);
        $attendance = $action->execute($dto);

        // Assert
        expect($attendance)->toBeInstanceOf(Attendance::class);
        expect($attendance->total_hours)->toBe(8.0); // 9 hours - 1 hour break = 8 hours
        expect($attendance->regular_hours)->toBe(8.0);
        expect($attendance->overtime_hours)->toBe(0.0);
        expect($attendance->break_hours)->toBe(1.0);
        expect($attendance->employee_id)->toBe($employee->id);
    });

    it('calculates overtime correctly', function () {
        // Arrange
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $date = '2023-10-26';

        $dto = new CreateAttendanceDTO(
            company_id: $this->company->id,
            employee_id: $employee->id,
            attendance_date: $date,
            clock_in_time: '09:00:00',
            clock_out_time: '19:00:00', // 10 hours total
            break_start_time: null,
            break_end_time: null,
            status: 'present',
            attendance_type: 'regular',
            clock_in_location: null,
            clock_out_location: null,
            clock_in_device: null,
            clock_out_device: null,
            clock_in_ip: null,
            clock_out_ip: null,
            notes: null,
            is_manual_entry: true,
            leave_request_id: null,
            created_by_user_id: $this->user->id,
        );

        // Act
        $action = app(CreateAttendanceAction::class);
        $attendance = $action->execute($dto);

        // Assert
        expect($attendance->total_hours)->toBe(10.0);
        expect($attendance->regular_hours)->toBe(8.0);
        expect($attendance->overtime_hours)->toBe(2.0);
    });

    it('handles missing clock out time', function () {
        // Arrange
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $date = '2023-10-27';

        $dto = new CreateAttendanceDTO(
            company_id: $this->company->id,
            employee_id: $employee->id,
            attendance_date: $date,
            clock_in_time: '09:00:00',
            clock_out_time: null,
            break_start_time: null,
            break_end_time: null,
            status: 'present',
            attendance_type: 'regular',
            clock_in_location: null,
            clock_out_location: null,
            clock_in_device: null,
            clock_out_device: null,
            clock_in_ip: null,
            clock_out_ip: null,
            notes: null,
            is_manual_entry: false,
            leave_request_id: null,
            created_by_user_id: $this->user->id,
        );

        // Act
        $action = app(CreateAttendanceAction::class);
        $attendance = $action->execute($dto);

        // Assert
        expect($attendance->clock_out_time)->toBeNull();
        expect($attendance->total_hours)->toBeNull();
        expect($attendance->regular_hours)->toBeNull();
    });
});

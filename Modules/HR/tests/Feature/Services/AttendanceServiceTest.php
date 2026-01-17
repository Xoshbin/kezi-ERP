<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Modules\HR\Services\HumanResources\AttendanceService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->service = app(AttendanceService::class);
});

describe('AttendanceService', function () {
    it('can clock in an employee', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        Carbon::setTestNow(Carbon::parse('2023-10-09 09:00:00'));

        $attendance = $this->service->clockIn($employee, 'Office', 'Web', '127.0.0.1');

        expect($attendance)
            ->toBeInstanceOf(Attendance::class)
            ->clock_in_time->toBe('09:00:00')
            ->status->toBe('present') // or whatever default status logic
            ->employee_id->toBe($employee->id);

        Carbon::setTestNow();
    });

    it('throws exception if clocking in twice same day', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        Carbon::setTestNow(Carbon::parse('2023-10-09 09:00:00'));

        $this->service->clockIn($employee);

        expect(fn () => $this->service->clockIn($employee))
            ->toThrow(Exception::class, 'Employee has already clocked in today.');

        Carbon::setTestNow();
    });

    it('can clock out an employee', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        Carbon::setTestNow(Carbon::parse('2023-10-09 09:00:00'));
        $this->service->clockIn($employee);

        Carbon::setTestNow(Carbon::parse('2023-10-09 17:00:00')); // 8 hours later
        $attendance = $this->service->clockOut($employee);

        expect($attendance)
            ->clock_out_time->toBe('17:00:00')
            ->total_hours->toBe(8.0)
            ->regular_hours->toBe(8.0)
            ->overtime_hours->toBe(0.0);

        Carbon::setTestNow();
    });

    it('calculates overtime correctly', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        Carbon::setTestNow(Carbon::parse('2023-10-09 09:00:00'));
        $this->service->clockIn($employee);

        Carbon::setTestNow(Carbon::parse('2023-10-09 19:00:00')); // 10 hours later
        $attendance = $this->service->clockOut($employee);

        expect($attendance)
            ->total_hours->toBe(10.0)
            ->regular_hours->toBe(8.0)
            ->overtime_hours->toBe(2.0);

        Carbon::setTestNow();
    });

    it('deducts break time from total hours', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        Carbon::setTestNow(Carbon::parse('2023-10-09 09:00:00'));
        $this->service->clockIn($employee);

        Carbon::setTestNow(Carbon::parse('2023-10-09 12:00:00'));

        $this->service->startBreak($employee);

        Carbon::setTestNow(Carbon::parse('2023-10-09 13:00:00')); // 1 hour break
        $this->service->endBreak($employee);

        Carbon::setTestNow(Carbon::parse('2023-10-09 18:00:00')); // 9 hours total span, 1 hour break
        $attendance = $this->service->clockOut($employee);

        // 9 hours elapsed - 1 hour break = 8 hours worked
        expect($attendance)
            ->total_hours->toBe(8.0);

        Carbon::setTestNow();
    });
});

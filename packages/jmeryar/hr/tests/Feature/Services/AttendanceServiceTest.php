<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\HR\Models\Attendance;
use Jmeryar\HR\Models\Employee;
use Jmeryar\HR\Services\HumanResources\AttendanceService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->service = app(AttendanceService::class);
});

describe('AttendanceService', function () {
    it('can clock in an employee', function () {
        $service = app(AttendanceService::class);
        /** @var \App\Models\Company $company */
        $company = $this->company;
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        Carbon::setTestNow(Carbon::parse('2023-10-09 09:00:00'));

        $attendance = $service->clockIn($employee, 'Office', 'Web', '127.0.0.1');

        expect($attendance)->toBeInstanceOf(Attendance::class);
        expect($attendance->clock_in_time)->toBe('09:00:00');
        expect($attendance->status)->toBe('present');
        expect($attendance->employee_id)->toBe($employee->id);

        Carbon::setTestNow();
    });

    it('throws exception if clocking in twice same day', function () {
        $service = app(AttendanceService::class);
        /** @var \App\Models\Company $company */
        $company = $this->company;
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        Carbon::setTestNow(Carbon::parse('2023-10-09 09:00:00'));

        $service->clockIn($employee);

        expect(fn () => $service->clockIn($employee))
            ->toThrow(Exception::class, 'Employee has already clocked in today.');

        Carbon::setTestNow();
    });

    it('can clock out an employee', function () {
        $service = app(AttendanceService::class);
        /** @var \App\Models\Company $company */
        $company = $this->company;
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        Carbon::setTestNow(Carbon::parse('2023-10-09 09:00:00'));
        $service->clockIn($employee);

        Carbon::setTestNow(Carbon::parse('2023-10-09 17:00:00')); // 8 hours later
        $attendance = $service->clockOut($employee);

        expect($attendance->clock_out_time)->toBe('17:00:00');
        expect($attendance->total_hours)->toBe(8.0);
        expect($attendance->regular_hours)->toBe(8.0);
        expect($attendance->overtime_hours)->toBe(0.0);

        Carbon::setTestNow();
    });

    it('calculates overtime correctly', function () {
        $service = app(AttendanceService::class);
        /** @var \App\Models\Company $company */
        $company = $this->company;
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        Carbon::setTestNow(Carbon::parse('2023-10-09 09:00:00'));
        $service->clockIn($employee);

        Carbon::setTestNow(Carbon::parse('2023-10-09 19:00:00')); // 10 hours later
        $attendance = $service->clockOut($employee);

        expect($attendance->total_hours)->toBe(10.0);
        expect($attendance->regular_hours)->toBe(8.0);
        expect($attendance->overtime_hours)->toBe(2.0);

        Carbon::setTestNow();
    });

    it('deducts break time from total hours', function () {
        $service = app(AttendanceService::class);
        /** @var \App\Models\Company $company */
        $company = $this->company;
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        Carbon::setTestNow(Carbon::parse('2023-10-09 09:00:00'));
        $service->clockIn($employee);

        Carbon::setTestNow(Carbon::parse('2023-10-09 12:00:00'));

        $service->startBreak($employee);

        Carbon::setTestNow(Carbon::parse('2023-10-09 13:00:00')); // 1 hour break
        $service->endBreak($employee);

        Carbon::setTestNow(Carbon::parse('2023-10-09 18:00:00')); // 9 hours total span, 1 hour break
        $attendance = $service->clockOut($employee);

        // 9 hours elapsed - 1 hour break = 8 hours worked
        expect($attendance->total_hours)->toBe(8.0);

        Carbon::setTestNow();
    });
});

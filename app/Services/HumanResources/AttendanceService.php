<?php

namespace App\Services\HumanResources;

use App\Actions\HumanResources\CreateAttendanceAction;
use App\DataTransferObjects\HumanResources\CreateAttendanceDTO;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Gate;

class AttendanceService
{
    public function __construct(
        protected CreateAttendanceAction $createAttendanceAction,
    ) {}

    /**
     * Clock in an employee.
     */
    public function clockIn(Employee $employee, ?string $location = null, ?string $device = null, ?string $ip = null, ?User $user = null): Attendance
    {
        $today = now()->format('Y-m-d');
        $currentTime = now()->format('H:i:s');

        // Check if already clocked in today
        $existingAttendance = $employee->getAttendanceForDate(now());
        if ($existingAttendance && $existingAttendance->clock_in_time) {
            throw new Exception('Employee has already clocked in today.');
        }

        $createAttendanceDTO = new CreateAttendanceDTO(
            company_id: $employee->company_id,
            employee_id: $employee->id,
            attendance_date: $today,
            clock_in_time: $currentTime,
            clock_out_time: null,
            break_start_time: null,
            break_end_time: null,
            status: 'present',
            attendance_type: $this->determineAttendanceType($today),
            clock_in_location: $location,
            clock_out_location: null,
            clock_in_device: $device,
            clock_out_device: null,
            clock_in_ip: $ip,
            clock_out_ip: null,
            notes: null,
            is_manual_entry: $user !== null,
            leave_request_id: null,
            created_by_user_id: $user->id ?? $employee->user_id ?? 1,
        );

        return $this->createAttendanceAction->execute($createAttendanceDTO);
    }

    /**
     * Clock out an employee.
     */
    public function clockOut(Employee $employee, ?string $location = null, ?string $device = null, ?string $ip = null): Attendance
    {
        $today = now()->format('Y-m-d');
        $currentTime = now()->format('H:i:s');

        $attendance = $employee->getAttendanceForDate(now());
        if (! $attendance || ! $attendance->clock_in_time) {
            throw new Exception('Employee has not clocked in today.');
        }

        if ($attendance->clock_out_time) {
            throw new Exception('Employee has already clocked out today.');
        }

        // Calculate total hours
        $clockIn = Carbon::parse($attendance->attendance_date.' '.$attendance->clock_in_time);
        $clockOut = Carbon::parse($attendance->attendance_date.' '.$currentTime);

        $totalMinutes = $clockOut->diffInMinutes($clockIn);

        // Subtract break time if recorded
        if ($attendance->break_start_time && $attendance->break_end_time) {
            $breakStart = Carbon::parse($attendance->attendance_date.' '.$attendance->break_start_time);
            $breakEnd = Carbon::parse($attendance->attendance_date.' '.$attendance->break_end_time);
            $breakMinutes = $breakEnd->diffInMinutes($breakStart);
            $totalMinutes -= $breakMinutes;
        }

        $totalHours = round($totalMinutes / 60, 2);
        $regularHours = min($totalHours, 8); // Assuming 8 hours is regular
        $overtimeHours = max(0, $totalHours - 8);

        $attendance->update([
            'clock_out_time' => $currentTime,
            'clock_out_location' => $location,
            'clock_out_device' => $device,
            'clock_out_ip' => $ip,
            'total_hours' => $totalHours,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
        ]);

        $freshAttendance = $attendance->fresh();
        if (! $freshAttendance) {
            throw new \Exception('Failed to refresh attendance after clock out');
        }

        return $freshAttendance;
    }

    /**
     * Start break for an employee.
     */
    public function startBreak(Employee $employee): Attendance
    {
        $attendance = $employee->getAttendanceForDate(now());
        if (! $attendance || ! $attendance->clock_in_time) {
            throw new Exception('Employee has not clocked in today.');
        }

        if ($attendance->break_start_time) {
            throw new Exception('Break has already been started.');
        }

        $attendance->update([
            'break_start_time' => now()->format('H:i:s'),
        ]);

        $freshAttendance = $attendance->fresh();
        if (! $freshAttendance) {
            throw new \Exception('Failed to refresh attendance after starting break');
        }

        return $freshAttendance;
    }

    /**
     * End break for an employee.
     */
    public function endBreak(Employee $employee): Attendance
    {
        $attendance = $employee->getAttendanceForDate(now());
        if (! $attendance || ! $attendance->break_start_time) {
            throw new Exception('Break has not been started.');
        }

        if ($attendance->break_end_time) {
            throw new Exception('Break has already been ended.');
        }

        $currentTime = now()->format('H:i:s');

        // Calculate break hours
        $breakStart = Carbon::parse($attendance->attendance_date.' '.$attendance->break_start_time);
        $breakEnd = Carbon::parse($attendance->attendance_date.' '.$currentTime);
        $breakHours = round($breakEnd->diffInMinutes($breakStart) / 60, 2);

        $attendance->update([
            'break_end_time' => $currentTime,
            'break_hours' => $breakHours,
        ]);

        $freshAttendance = $attendance->fresh();
        if (! $freshAttendance) {
            throw new \Exception('Failed to refresh attendance after ending break');
        }

        return $freshAttendance;
    }

    /**
     * Create manual attendance entry.
     */
    public function createManualAttendance(CreateAttendanceDTO $createAttendanceDTO, User $user): Attendance
    {
        Gate::forUser($user)->authorize('create', Attendance::class);

        // Mark as manual entry
        $manualAttendanceDTO = new CreateAttendanceDTO(
            company_id: $createAttendanceDTO->company_id,
            employee_id: $createAttendanceDTO->employee_id,
            attendance_date: $createAttendanceDTO->attendance_date,
            clock_in_time: $createAttendanceDTO->clock_in_time,
            clock_out_time: $createAttendanceDTO->clock_out_time,
            break_start_time: $createAttendanceDTO->break_start_time,
            break_end_time: $createAttendanceDTO->break_end_time,
            status: $createAttendanceDTO->status,
            attendance_type: $createAttendanceDTO->attendance_type,
            clock_in_location: $createAttendanceDTO->clock_in_location,
            clock_out_location: $createAttendanceDTO->clock_out_location,
            clock_in_device: $createAttendanceDTO->clock_in_device,
            clock_out_device: $createAttendanceDTO->clock_out_device,
            clock_in_ip: $createAttendanceDTO->clock_in_ip,
            clock_out_ip: $createAttendanceDTO->clock_out_ip,
            notes: $createAttendanceDTO->notes,
            is_manual_entry: true,
            leave_request_id: $createAttendanceDTO->leave_request_id,
            created_by_user_id: $user->id,
        );

        return $this->createAttendanceAction->execute($manualAttendanceDTO);
    }

    /**
     * Get attendance summary for an employee.
     *
     * @return array<string, mixed>
     */
    public function getAttendanceSummary(Employee $employee, string $startDate, string $endDate): array
    {
        $attendances = $employee->attendances()
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        $totalDays = $attendances->count();
        $presentDays = $attendances->where('status', 'present')->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $leaveDays = $attendances->where('status', 'on_leave')->count();

        $totalHours = $attendances->sum('total_hours') ?? 0;
        $regularHours = $attendances->sum('regular_hours') ?? 0;
        $overtimeHours = $attendances->sum('overtime_hours') ?? 0;

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'days' => [
                'total_days' => $totalDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'late_days' => $lateDays,
                'leave_days' => $leaveDays,
            ],
            'hours' => [
                'total_hours' => $totalHours,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'average_hours_per_day' => $presentDays > 0 ? round($totalHours / $presentDays, 2) : 0,
            ],
            'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0,
        ];
    }

    /**
     * Determine attendance type based on date.
     */
    private function determineAttendanceType(string $date): string
    {
        $carbonDate = Carbon::parse($date);

        if ($carbonDate->isWeekend()) {
            return 'weekend';
        }

        // TODO: Check for holidays
        // if ($this->isHoliday($carbonDate)) {
        //     return 'holiday';
        // }

        return 'regular';
    }
}

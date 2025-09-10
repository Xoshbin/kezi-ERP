<?php

namespace App\Actions\HumanResources;

use App\DataTransferObjects\HumanResources\CreateAttendanceDTO;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateAttendanceAction
{
    public function execute(CreateAttendanceDTO $createAttendanceDTO): Attendance
    {
        return DB::transaction(function () use ($createAttendanceDTO): Attendance {
            // Calculate total hours if clock in/out times are provided
            $totalHours = null;
            $regularHours = null;
            $overtimeHours = null;
            $breakHours = null;

            if ($createAttendanceDTO->clock_in_time && $createAttendanceDTO->clock_out_time) {
                $clockIn = Carbon::parse($createAttendanceDTO->attendance_date.' '.$createAttendanceDTO->clock_in_time);
                $clockOut = Carbon::parse($createAttendanceDTO->attendance_date.' '.$createAttendanceDTO->clock_out_time);

                $totalMinutes = $clockOut->diffInMinutes($clockIn);

                // Calculate break time if provided
                if ($createAttendanceDTO->break_start_time && $createAttendanceDTO->break_end_time) {
                    $breakStart = Carbon::parse($createAttendanceDTO->attendance_date.' '.$createAttendanceDTO->break_start_time);
                    $breakEnd = Carbon::parse($createAttendanceDTO->attendance_date.' '.$createAttendanceDTO->break_end_time);
                    $breakMinutes = $breakEnd->diffInMinutes($breakStart);
                    $breakHours = round($breakMinutes / 60, 2);
                    $totalMinutes -= $breakMinutes;
                }

                $totalHours = round($totalMinutes / 60, 2);

                // Calculate regular vs overtime hours (assuming 8 hours is regular)
                $regularHours = min($totalHours, 8);
                $overtimeHours = max(0, $totalHours - 8);
            }

            $attendance = Attendance::create([
                'company_id' => $createAttendanceDTO->company_id,
                'employee_id' => $createAttendanceDTO->employee_id,
                'attendance_date' => $createAttendanceDTO->attendance_date,
                'clock_in_time' => $createAttendanceDTO->clock_in_time,
                'clock_out_time' => $createAttendanceDTO->clock_out_time,
                'break_start_time' => $createAttendanceDTO->break_start_time,
                'break_end_time' => $createAttendanceDTO->break_end_time,
                'total_hours' => $totalHours,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'break_hours' => $breakHours,
                'status' => $createAttendanceDTO->status,
                'attendance_type' => $createAttendanceDTO->attendance_type,
                'clock_in_location' => $createAttendanceDTO->clock_in_location,
                'clock_out_location' => $createAttendanceDTO->clock_out_location,
                'clock_in_device' => $createAttendanceDTO->clock_in_device,
                'clock_out_device' => $createAttendanceDTO->clock_out_device,
                'clock_in_ip' => $createAttendanceDTO->clock_in_ip,
                'clock_out_ip' => $createAttendanceDTO->clock_out_ip,
                'notes' => $createAttendanceDTO->notes,
                'is_manual_entry' => $createAttendanceDTO->is_manual_entry,
                'leave_request_id' => $createAttendanceDTO->leave_request_id,
            ]);

            $fresh = $attendance->fresh();
            if (! $fresh) {
                throw new \RuntimeException('Failed to refresh attendance after creation');
            }

            return $fresh;
        });
    }
}

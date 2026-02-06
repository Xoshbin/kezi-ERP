<?php

namespace Kezi\HR\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $attendance_date
 * @property string|null $clock_in_time
 * @property string|null $clock_out_time
 * @property string|null $break_start_time
 * @property string|null $break_end_time
 * @property float|null $total_hours
 * @property float|null $regular_hours
 * @property float|null $overtime_hours
 * @property float|null $break_hours
 * @property string $status
 * @property string $attendance_type
 * @property string|null $clock_in_location
 * @property string|null $clock_out_location
 * @property string|null $clock_in_device
 * @property string|null $clock_out_device
 * @property string|null $clock_in_ip
 * @property string|null $clock_out_ip
 * @property string|null $notes
 * @property bool $is_manual_entry
 * @property int|null $approved_by_user_id
 * @property string|null $approved_at
 * @property int|null $leave_request_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read \Kezi\HR\Models\Employee $employee
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereApprovedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereAttendanceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereAttendanceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereBreakEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereBreakHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereBreakStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockInDevice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockInIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockInLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockInTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockOutDevice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockOutIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockOutLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereClockOutTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereIsManualEntry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereLeaveRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereOvertimeHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereRegularHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereTotalHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereUpdatedAt($value)
 * @method static \Kezi\HR\Database\Factories\AttendanceFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_date',
        'clock_in_time',
        'clock_out_time',
        'break_start_time',
        'break_end_time',
        'total_hours',
        'regular_hours',
        'overtime_hours',
        'break_hours',
        'status',
        'attendance_type',
        'clock_in_location',
        'clock_out_location',
        'clock_in_device',
        'clock_out_device',
        'clock_in_ip',
        'clock_out_ip',
        'notes',
        'is_manual_entry',
        'leave_request_id',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'is_manual_entry' => 'boolean',
        'total_hours' => 'float',
        'regular_hours' => 'float',
        'overtime_hours' => 'float',
        'break_hours' => 'float',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function newFactory()
    {
        return \Kezi\HR\Database\Factories\AttendanceFactory::new();
    }
}

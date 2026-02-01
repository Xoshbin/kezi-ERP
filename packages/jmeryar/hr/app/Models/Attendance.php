<?php

namespace Jmeryar\HR\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        return \Jmeryar\HR\Database\Factories\AttendanceFactory::new();
    }
}

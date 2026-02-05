<?php

namespace Kezi\HR\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property string $request_number
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property float $days_requested
 * @property string|null $reason
 * @property string|null $notes
 * @property string $status
 * @property int|null $approved_by_user_id
 * @property Carbon|null $approved_at
 * @property string|null $approval_notes
 * @property string|null $rejection_reason
 * @property array<int, string>|null $attachments
 * @property int|null $delegate_employee_id
 * @property string|null $delegation_notes
 * @property int $requested_by_user_id
 * @property Carbon|null $submitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Employee $employee
 * @property-read LeaveType $leaveType
 * @property-read Employee|null $delegateEmployee
 * @property-read User|null $approvedByUser
 * @property-read User $requestedByUser
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereApprovalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereApprovedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereDaysRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereDelegateEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereDelegationNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereRequestNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereRequestedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveRequest whereUpdatedAt($value)
 * @method static \Kezi\HR\Database\Factories\LeaveRequestFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class LeaveRequest extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected static function newFactory()
    {
        return \Kezi\HR\Database\Factories\LeaveRequestFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'request_number',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'notes',
        'status',
        'approved_by_user_id',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'attachments',
        'delegate_employee_id',
        'delegation_notes',
        'requested_by_user_id',
        'submitted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_requested' => 'decimal:2',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function delegateEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'delegate_employee_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}

<?php

namespace App\Models;

use App\Traits\TranslatableSearch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * Class LeaveType
 *
 * @property int $id
 * @property int $company_id
 * @property array<string, string> $name
 * @property string $code
 * @property string|null $description
 * @property int $default_days_per_year
 * @property bool $requires_approval
 * @property bool $is_paid
 * @property bool $carries_forward
 * @property int $max_carry_forward_days
 * @property int|null $max_consecutive_days
 * @property int $min_notice_days
 * @property bool $requires_documentation
 * @property string $color
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Collection<int, LeaveRequest> $leaveRequests
 * @property-read int|null $leave_requests_count
 */
class LeaveType extends Model
{
    use HasFactory, HasTranslations;
    use TranslatableSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'default_days_per_year',
        'requires_approval',
        'is_paid',
        'carries_forward',
        'max_carry_forward_days',
        'max_consecutive_days',
        'min_notice_days',
        'requires_documentation',
        'color',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'array',
        'default_days_per_year' => 'integer',
        'requires_approval' => 'boolean',
        'is_paid' => 'boolean',
        'carries_forward' => 'boolean',
        'max_carry_forward_days' => 'integer',
        'max_consecutive_days' => 'integer',
        'min_notice_days' => 'integer',
        'requires_documentation' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = ['name'];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'default_days_per_year' => 0,
        'requires_approval' => true,
        'is_paid' => true,
        'carries_forward' => false,
        'max_carry_forward_days' => 0,
        'min_notice_days' => 1,
        'requires_documentation' => false,
        'color' => '#3B82F6',
        'is_active' => true,
    ];

    /**
     * Get the translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getTranslatableSearchFields(): array
    {
        return ['name'];
    }

    /**
     * Get the non-translatable fields that should be searched.
     *
     * @return array<int, string>
     */
    public function getNonTranslatableSearchFields(): array
    {
        return ['code', 'description'];
    }

    /**
     * Get the company that owns the LeaveType.
     */
    /**

     * @return BelongsTo<Company, static>

     */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the leave requests for this leave type.
     */
    /**

     * @return HasMany<LeaveRequest, static>

     */

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this leave type requires manager approval.
     */
    public function requiresApproval(): bool
    {
        return $this->requires_approval;
    }

    /**
     * Check if this leave type is paid.
     */
    public function isPaid(): bool
    {
        return $this->is_paid;
    }

    /**
     * Check if unused days can be carried forward to next year.
     */
    public function canCarryForward(): bool
    {
        return $this->carries_forward;
    }

    /**
     * Get the maximum days that can be carried forward.
     */
    public function getMaxCarryForwardDays(): int
    {
        return $this->carries_forward ? $this->max_carry_forward_days : 0;
    }

    /**
     * Check if documentation is required for this leave type.
     */
    public function requiresDocumentation(): bool
    {
        return $this->requires_documentation;
    }

    /**
     * Get the minimum notice required in days.
     */
    public function getMinimumNotice(): int
    {
        return $this->min_notice_days;
    }

    /**
     * Check if the requested days exceed the maximum consecutive days allowed.
     */
    public function exceedsMaxConsecutiveDays(int $requestedDays): bool
    {
        if (! $this->max_consecutive_days) {
            return false;
        }

        return $requestedDays > $this->max_consecutive_days;
    }

    /**
     * Get the total leave requests for this type in the current year.
     */
    public function getTotalRequestsThisYear(): int
    {
        return $this->leaveRequests()
            ->whereYear('start_date', now()->year)
            ->where('status', 'approved')
            ->count();
    }

    /**
     * Get the total days requested for this type in the current year.
     */
    public function getTotalDaysRequestedThisYear(): float
    {
        return (float) $this->leaveRequests()
            ->whereYear('start_date', now()->year)
            ->where('status', 'approved')
            ->sum('days_requested');
    }

    /**
     * Get common leave type codes.
     *
     * @return array<string, string>
     */
    public static function getCommonLeaveCodes(): array
    {
        return [
            'annual' => 'Annual Leave',
            'sick' => 'Sick Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
            'emergency' => 'Emergency Leave',
            'bereavement' => 'Bereavement Leave',
            'study' => 'Study Leave',
            'unpaid' => 'Unpaid Leave',
            'compensatory' => 'Compensatory Leave',
            'religious' => 'Religious Leave',
        ];
    }
}

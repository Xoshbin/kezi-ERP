<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kezi\Accounting\Database\Factories\RecurringTemplateFactory;
use Kezi\Accounting\Enums\Accounting\RecurringFrequency;
use Kezi\Accounting\Enums\Accounting\RecurringStatus;
use Kezi\Accounting\Enums\Accounting\RecurringTargetType;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property RecurringFrequency $frequency
 * @property int $interval
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon $next_run_date
 * @property RecurringStatus $status
 * @property RecurringTargetType $target_type
 * @property array<array-key, mixed> $template_data
 * @property int|null $created_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Company $company
 * @property-read User|null $createdByUser
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereCreatedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereNextRunDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereTargetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereTemplateData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecurringTemplate withoutTrashed()
 * @method static \Kezi\Accounting\Database\Factories\RecurringTemplateFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class RecurringTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'frequency',
        'interval',
        'start_date',
        'end_date',
        'next_run_date',
        'status',
        'target_type',
        'template_data',
        'created_by_user_id',
    ];

    protected $casts = [
        'frequency' => RecurringFrequency::class,
        'status' => RecurringStatus::class,
        'target_type' => RecurringTargetType::class,
        'template_data' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_run_date' => 'date',
    ];

    protected static function newFactory(): RecurringTemplateFactory
    {
        return RecurringTemplateFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

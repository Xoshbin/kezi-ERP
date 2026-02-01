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

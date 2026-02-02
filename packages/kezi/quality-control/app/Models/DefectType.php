<?php

namespace Kezi\QualityControl\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Foundation\Observers\AuditLogObserver;

#[ObservedBy([AuditLogObserver::class])]
class DefectType extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function newFactory(): \Kezi\QualityControl\Database\Factories\DefectTypeFactory
    {
        return \Kezi\QualityControl\Database\Factories\DefectTypeFactory::new();
    }

    /**
     * @return HasMany<QualityAlert, static>
     */
    public function qualityAlerts(): HasMany
    {
        return $this->hasMany(QualityAlert::class);
    }
}

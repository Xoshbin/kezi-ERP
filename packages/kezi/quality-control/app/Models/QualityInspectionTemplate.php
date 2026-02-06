<?php

namespace Kezi\QualityControl\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Foundation\Observers\AuditLogObserver;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\QualityControl\Models\QualityControlPoint> $controlPoints
 * @property-read int|null $control_points_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\QualityControl\Models\QualityInspectionParameter> $parameters
 * @property-read int|null $parameters_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\QualityControl\Models\QualityCheck> $qualityChecks
 * @property-read int|null $quality_checks_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionTemplate whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionTemplate whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityInspectionTemplate whereUpdatedAt($value)
 * @method static \Kezi\QualityControl\Database\Factories\QualityInspectionTemplateFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([AuditLogObserver::class])]
class QualityInspectionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
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

    /**
     * @return HasMany<QualityInspectionParameter, static>
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(QualityInspectionParameter::class, 'template_id');
    }

    /**
     * @return HasMany<QualityControlPoint, static>
     */
    public function controlPoints(): HasMany
    {
        return $this->hasMany(QualityControlPoint::class, 'inspection_template_id');
    }

    /**
     * @return HasMany<QualityCheck, static>
     */
    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class, 'inspection_template_id');
    }

    protected static function newFactory(): \Kezi\QualityControl\Database\Factories\QualityInspectionTemplateFactory
    {
        return \Kezi\QualityControl\Database\Factories\QualityInspectionTemplateFactory::new();
    }
}

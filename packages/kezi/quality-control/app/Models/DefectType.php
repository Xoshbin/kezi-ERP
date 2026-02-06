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
/**
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\QualityControl\Models\QualityAlert> $qualityAlerts
 * @property-read int|null $quality_alerts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefectType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefectType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefectType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefectType whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefectType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefectType whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefectType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefectType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefectType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefectType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefectType whereUpdatedAt($value)
 * @method static \Kezi\QualityControl\Database\Factories\DefectTypeFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
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
